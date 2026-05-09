<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Controller;

use Carbon\Carbon;
use Datlechin\Passkey\Event\PasskeyCounterRegression;
use Datlechin\Passkey\Event\PasskeyUsed;
use Datlechin\Passkey\Exception\PasskeyVerificationException;
use Datlechin\Passkey\Model\Passkey;
use Datlechin\Passkey\WebAuthn\ChallengeStore;
use Datlechin\Passkey\WebAuthn\ServerFactory;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAccessToken;
use Flarum\Http\SessionAuthenticator;
use Flarum\User\Event\LoggedIn;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\LoginProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginController implements RequestHandlerInterface
{
    public function __construct(
        private readonly ServerFactory $server,
        private readonly ChallengeStore $challengeStore,
        private readonly SessionAuthenticator $authenticator,
        private readonly Rememberer $rememberer,
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $credentialJson = Arr::get($body, 'credential');
        $remember = (bool) Arr::get($body, 'remember', true);

        if (! is_array($credentialJson) && ! is_string($credentialJson)) {
            throw new PasskeyVerificationException('Missing credential payload.');
        }

        $session = $request->getAttribute('session');
        $serializedOptions = $this->challengeStore->takeAssertion($session);
        if ($serializedOptions === null) {
            throw new PasskeyVerificationException('Assertion challenge expired or missing.');
        }

        $serializer = $this->server->serializer();

        /** @var PublicKeyCredentialRequestOptions $options */
        $options = $serializer->deserialize($serializedOptions, PublicKeyCredentialRequestOptions::class, 'json');

        $credentialPayload = is_string($credentialJson) ? $credentialJson : json_encode($credentialJson, JSON_THROW_ON_ERROR);
        $publicKeyCredential = $serializer->deserialize($credentialPayload, PublicKeyCredential::class, 'json');

        $response = $publicKeyCredential->response;
        if (! $response instanceof AuthenticatorAssertionResponse) {
            throw new PasskeyVerificationException('Expected an assertion response.');
        }

        $credentialId = $publicKeyCredential->rawId;

        $passkey = Passkey::where('credential_id', $credentialId)->first();
        if ($passkey === null) {
            throw new NotAuthenticatedException;
        }

        $user = $passkey->user;
        if ($user === null || $user->is_email_confirmed !== true) {
            throw new NotAuthenticatedException;
        }
        if (! $user->can('viewForum')) {
            throw new NotAuthenticatedException;
        }

        $previousBackupState = $passkey->backup_state;

        $credentialRecord = $passkey->toCredentialRecord();

        try {
            // userHandle: null because the credential lookup above already
            // pinned the owner; the lib re-verifies the user handle against
            // the stored record rather than trusting client input.
            $updated = $this->server->assertionValidator()->check(
                credentialRecord: $credentialRecord,
                authenticatorAssertionResponse: $response,
                publicKeyCredentialRequestOptions: $options,
                host: $request->getUri()->getHost(),
                userHandle: null,
            );
        } catch (Throwable $e) {
            if ($this->isCounterRegression($e)) {
                $this->events->dispatch(new PasskeyCounterRegression(
                    owner: $user,
                    passkey: $passkey,
                    previousCounter: $credentialRecord->counter,
                    observedCounter: (int) $response->authenticatorData->signCount,
                    request: $request,
                ));
            }

            // Log the underlying reason; client gets a fixed message so an
            // attacker cannot probe which step rejected.
            $this->logger->warning('Passkey assertion verification failed', ['exception' => $e]);
            throw new PasskeyVerificationException;
        }

        $passkey->syncFromCredentialRecord($updated);
        $passkey->last_used_at = Carbon::now();
        $passkey->last_used_ip = $request->getAttribute('ipAddress');
        $passkey->save();

        // Updates login_providers.last_login_at and double-checks the
        // credential -> user mapping is consistent with the stored row.
        $loginProviderUser = LoginProvider::logIn('passkey', $passkey->getProviderIdentifier());
        if ($loginProviderUser === null || $loginProviderUser->id !== $user->id) {
            $this->logger->error('Passkey verified but login_providers link missing or mismatched', [
                'user_id' => $user->id,
                'passkey_id' => $passkey->id,
            ]);
            throw new PasskeyVerificationException;
        }

        $token = $remember
            ? RememberAccessToken::generate($user->id)
            : SessionAccessToken::generate($user->id);
        $token->touch(request: $request);

        $this->authenticator->logIn($session, $token);

        $this->events->dispatch(new LoggedIn($user, $token));
        $this->events->dispatch(new PasskeyUsed(
            user: $user,
            passkey: $passkey,
            request: $request,
            backupStateChanged: $previousBackupState !== $passkey->backup_state,
        ));

        $jsonResponse = new JsonResponse([
            'token' => $token->token,
            'userId' => $user->id,
        ]);

        return $token instanceof RememberAccessToken
            ? $this->rememberer->remember($jsonResponse, $token)
            : $jsonResponse;
    }

    private function isCounterRegression(Throwable $e): bool
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof \Webauthn\Exception\CounterException) {
                return true;
            }
        }

        return false;
    }
}
