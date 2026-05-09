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

use Datlechin\Passkey\WebAuthn\ChallengeStore;
use Datlechin\Passkey\WebAuthn\OptionsBuilder;
use Datlechin\Passkey\WebAuthn\ServerFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Issues PublicKeyCredentialRequestOptions for an unauthenticated assertion.
 *
 * Always returns an empty `allowCredentials` list so that:
 *   1. Discoverable (resident) credentials drive the UX, the user does not
 *      have to type a username before the prompt opens.
 *   2. The endpoint never leaks which usernames have a passkey registered;
 *      enumeration via this endpoint returns the same shape for every caller.
 */
class LoginOptionsController implements RequestHandlerInterface
{
    public function __construct(
        private readonly ServerFactory $server,
        private readonly OptionsBuilder $optionsBuilder,
        private readonly ChallengeStore $challengeStore,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $options = $this->optionsBuilder->buildRequestOptions(
            allowCredentials: [],
            host: $request->getUri()->getHost(),
        );

        $serialized = $this->server->serializer()->serialize($options, 'json', [
            'json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        $this->challengeStore->putAssertion($request->getAttribute('session'), $serialized);

        return new JsonResponse(json_decode($serialized, true));
    }
}
