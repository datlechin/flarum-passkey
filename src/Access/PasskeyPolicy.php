<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Access;

use Datlechin\Passkey\Model\Passkey;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

/**
 * Authorizes update and delete on a passkey row only for the credential owner.
 *
 * Returning `null` from a policy method means "no opinion", at which point the
 * core gate falls back to its deny-by-default. Returning `true` short-circuits
 * to allow.
 */
class PasskeyPolicy extends AbstractPolicy
{
    public function update(User $actor, Passkey $passkey): ?bool
    {
        return $actor->id === $passkey->user_id ? true : null;
    }

    public function delete(User $actor, Passkey $passkey): ?bool
    {
        // Admins may revoke any passkey for support cases (e.g. a user has
        // lost every device and asks an admin to wipe their credentials).
        return ($actor->id === $passkey->user_id || $actor->isAdmin()) ? true : null;
    }
}
