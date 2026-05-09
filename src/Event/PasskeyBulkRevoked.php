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

use Flarum\User\User;

/**
 * Dispatched when a user (or moderator on their behalf) revokes every passkey
 * on an account in a single action. Bulk revoke does not emit per-credential
 * {@see PasskeyRevoked} events so listeners that send notifications can mail
 * once instead of once per credential.
 */
final class PasskeyBulkRevoked
{
    public function __construct(
        public readonly User $owner,
        public readonly User $actor,
        public readonly int $count,
    ) {
    }
}
