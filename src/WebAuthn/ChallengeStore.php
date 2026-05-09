<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\WebAuthn;

use Illuminate\Contracts\Session\Session;

/**
 * Stores per-ceremony WebAuthn challenges in the user's HTTP session.
 *
 * A challenge is a single-use nonce: it is consumed by {@see take()} as part of
 * verification, so a stolen or replayed assertion targeting the same challenge
 * will fail because the second lookup returns null.
 *
 * Two namespaces are used so that an in-flight registration does not collide
 * with an in-flight assertion (e.g. a user adding a second passkey while the
 * login modal is still open in another tab).
 */
class ChallengeStore
{
    public const REGISTRATION_KEY = 'datlechin-passkey.registration_options';
    public const ASSERTION_KEY = 'datlechin-passkey.assertion_options';

    public function putRegistration(Session $session, string $serializedOptions): void
    {
        $session->put(self::REGISTRATION_KEY, $serializedOptions);
    }

    public function takeRegistration(Session $session): ?string
    {
        return $this->take($session, self::REGISTRATION_KEY);
    }

    public function putAssertion(Session $session, string $serializedOptions): void
    {
        $session->put(self::ASSERTION_KEY, $serializedOptions);
    }

    public function takeAssertion(Session $session): ?string
    {
        return $this->take($session, self::ASSERTION_KEY);
    }

    private function take(Session $session, string $key): ?string
    {
        $value = $session->get($key);
        $session->forget($key);

        return is_string($value) ? $value : null;
    }
}
