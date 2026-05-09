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

use Datlechin\Passkey\Model\Passkey;
use Datlechin\Passkey\WebAuthn\ChallengeStore;
use Datlechin\Passkey\WebAuthn\OptionsBuilder;
use Datlechin\Passkey\WebAuthn\ServerFactory;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\NotAuthenticatedException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Issues PublicKeyCredentialCreationOptions for a logged-in user adding a new
 * passkey. The serialized options are stashed in the session so the matching
 * registration endpoint can verify the same challenge.
 */
class RegistrationOptionsController implements RequestHandlerInterface
{
    public function __construct(
        private readonly ServerFactory $server,
        private readonly OptionsBuilder $optionsBuilder,
        private readonly ChallengeStore $challengeStore,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        if ($actor->isGuest()) {
            throw new NotAuthenticatedException;
        }

        $existing = Passkey::where('user_id', $actor->id)->get()->all();

        $options = $this->optionsBuilder->buildCreationOptions(
            user: $actor,
            existingPasskeys: $existing,
            host: $request->getUri()->getHost(),
        );

        $serialized = $this->server->serializer()->serialize($options, 'json', [
            'json_encode_options' => JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            // Older Safari builds reject the ceremony when fields like
            // `requireResidentKey` or `authenticatorAttachment` arrive as
            // explicit `null`. Drop nulls so the JSON only carries the
            // fields the platform actually needs to look at.
            \Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);

        $this->challengeStore->putRegistration($request->getAttribute('session'), $serialized);

        return new JsonResponse(json_decode($serialized, true));
    }
}
