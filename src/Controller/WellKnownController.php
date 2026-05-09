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

use Datlechin\Passkey\WebAuthn\ServerFactory;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Serves `/.well-known/webauthn` for W3C Related Origin Requests.
 *
 * When a relying party is reachable from multiple top-level origins (e.g. the
 * forum at `forum.example.com` and a marketing site at `example.org` that
 * embeds a sign-in widget), browsers fetch this document during a passkey
 * ceremony to confirm that the current origin is permitted to use the
 * configured RP id. The list is purely declarative and can be empty if the
 * forum is single-host.
 *
 * @see https://w3c.github.io/webauthn/#sctn-related-origins
 */
class WellKnownController implements RequestHandlerInterface
{
    public function __construct(
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $raw = (string) $this->settings->get('datlechin-passkey.related_origins');
        $origins = ServerFactory::parseRelatedOrigins($raw);

        return new JsonResponse(['origins' => array_values(array_unique($origins))]);
    }
}
