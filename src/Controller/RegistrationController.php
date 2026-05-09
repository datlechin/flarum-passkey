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

use Datlechin\Passkey\Event\PasskeyRegistered;
use Datlechin\Passkey\Exception\PasskeyVerificationException;
use Datlechin\Passkey\Model\Passkey;
use Datlechin\Passkey\WebAuthn\ChallengeStore;
use Datlechin\Passkey\WebAuthn\ServerFactory;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\LoginProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * Verifies an attestation response and persists the new passkey.
 *
 * Both the `passkeys` row and the `login_providers` link are written inside a
 * single transaction so an interruption can never leave a credential without
 * its login route (which would silently lock the user out of that device).
 */
class RegistrationController implements RequestHandlerInterface
{
    public function __construct(
        private readonly ServerFactory $server,
        private readonly ChallengeStore $challengeStore,
        private readonly Dispatcher $events,
        private readonly ConnectionInterface $db,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        if ($actor->isGuest()) {
            throw new NotAuthenticatedException;
        }

        $body = $request->getParsedBody();
        $credentialJson = Arr::get($body, 'credential');
        $deviceName = trim((string) Arr::get($body, 'deviceName', ''));

        if (! is_array($credentialJson) && ! is_string($credentialJson)) {
            throw new PasskeyVerificationException('Missing credential payload.');
        }
        if ($deviceName === '') {
            $deviceName = 'Passkey';
        }
        if (mb_strlen($deviceName) > 64) {
            $deviceName = mb_substr($deviceName, 0, 64);
        }

        $serializedOptions = $this->challengeStore->takeRegistration($request->getAttribute('session'));
        if ($serializedOptions === null) {
            throw new PasskeyVerificationException('Registration challenge expired or missing.');
        }

        $serializer = $this->server->serializer();

        $options = $serializer->deserialize($serializedOptions, PublicKeyCredentialCreationOptions::class, 'json');

        $credentialPayload = is_string($credentialJson) ? $credentialJson : json_encode($credentialJson, JSON_THROW_ON_ERROR);
        $publicKeyCredential = $serializer->deserialize($credentialPayload, PublicKeyCredential::class, 'json');

        $response = $publicKeyCredential->response;
        if (! $response instanceof AuthenticatorAttestationResponse) {
            throw new PasskeyVerificationException('Expected an attestation response.');
        }

        try {
            $credentialRecord = $this->server->attestationValidator()->check(
                authenticatorAttestationResponse: $response,
                publicKeyCredentialCreationOptions: $options,
                host: $request->getUri()->getHost(),
            );
        } catch (Throwable $e) {
            $this->logger->warning('Passkey attestation verification failed', ['exception' => $e]);
            throw new PasskeyVerificationException;
        }

        $passkey = $this->db->transaction(function () use ($credentialRecord, $actor, $deviceName, $request) {
            $passkey = new Passkey;
            $passkey->user_id = $actor->id;
            $passkey->credential_id = Passkey::base64UrlEncode($credentialRecord->publicKeyCredentialId);
            $passkey->public_key_cose = Passkey::base64UrlEncode($credentialRecord->credentialPublicKey);
            $passkey->signature_count = $credentialRecord->counter;
            $passkey->transports = $credentialRecord->transports;
            $passkey->aaguid = $credentialRecord->aaguid->toRfc4122();
            $passkey->attestation_format = $credentialRecord->attestationType;
            $passkey->backup_eligible = (bool) $credentialRecord->backupEligible;
            $passkey->backup_state = (bool) $credentialRecord->backupStatus;
            $passkey->uv_initialized = (bool) $credentialRecord->uvInitialized;
            $passkey->device_name = $deviceName;
            $passkey->user_agent_summary = mb_substr((string) ($request->getServerParams()['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null;
            $passkey->save();

            // Use the hasMany relation so `user_id` is auto-set from the parent
            // user. LoginProvider's `$fillable` is `['provider', 'identifier']`
            // only, so mass-assigning `user_id` via firstOrCreate() would silently
            // drop it and the resulting INSERT would fail the FK to users.
            $actor->loginProviders()->firstOrCreate([
                'provider' => 'passkey',
                'identifier' => $passkey->getProviderIdentifier(),
            ]);

            return $passkey;
        });

        $this->events->dispatch(new PasskeyRegistered($actor, $passkey, $request));

        return new JsonResponse(
            ['data' => $this->serializePasskey($passkey)],
            201
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePasskey(Passkey $passkey): array
    {
        return [
            'type' => 'passkeys',
            'id' => (string) $passkey->id,
            'attributes' => [
                'deviceName' => $passkey->device_name,
                'aaguid' => $passkey->aaguid,
                'transports' => $passkey->transports,
                'backupEligible' => $passkey->backup_eligible,
                'backupState' => $passkey->backup_state,
                'createdAt' => $passkey->created_at->toIso8601String(),
                'lastUsedAt' => $passkey->last_used_at?->toIso8601String(),
            ],
        ];
    }
}
