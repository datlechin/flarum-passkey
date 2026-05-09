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
 * Dispatched when an authenticator returns a signature counter lower than the
 * one stored, a strong signal the credential has been cloned to a second
 * device. The lib has already rejected the assertion at this point; the event
 * exists so listeners (audit logs, owner notifications) can react.
 */
final class PasskeyCounterRegression
{
    public function __construct(
        public readonly User $owner,
        public readonly Passkey $passkey,
        public readonly int $previousCounter,
        public readonly int $observedCounter,
        public readonly ServerRequestInterface $request,
    ) {
    }
}
