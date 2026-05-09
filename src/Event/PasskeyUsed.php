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
 * Dispatched after a successful sign-in via passkey. `$backupStateChanged` is
 * true when the BS bit reported by the authenticator differs from the value we
 * had stored, a security signal worth surfacing to the owner (the credential
 * has just been synced or unsynced from a passkey provider).
 */
final class PasskeyUsed
{
    public function __construct(
        public readonly User $user,
        public readonly Passkey $passkey,
        public readonly ServerRequestInterface $request,
        public readonly bool $backupStateChanged,
    ) {
    }
}
