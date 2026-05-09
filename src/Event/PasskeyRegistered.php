<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Event;

use Datlechin\Passkey\Model\Passkey;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatched after a user has successfully registered a new passkey, both the
 * `passkeys` row and the `login_providers` link have been persisted.
 */
final class PasskeyRegistered
{
    public function __construct(
        public readonly User $user,
        public readonly Passkey $passkey,
        public readonly ServerRequestInterface $request,
    ) {
    }
}
