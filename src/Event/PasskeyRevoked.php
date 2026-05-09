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

/**
 * Dispatched when a passkey is revoked. `$actor` is the user who initiated
 * the revoke, which may differ from the credential owner if a moderator
 * revoked it on the user's behalf.
 */
final class PasskeyRevoked
{
    public function __construct(
        public readonly User $owner,
        public readonly User $actor,
        public readonly Passkey $passkey,
    ) {
    }
}
