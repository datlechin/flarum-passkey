<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey;

use Illuminate\Contracts\Events\Dispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Bridges the PSR-14 event dispatcher that web-auth/webauthn-lib expects onto
 * Flarum's Illuminate event bus.
 *
 * The webauthn library emits {@see \Webauthn\Event\BackupEligibilityChangedEvent}
 * and {@see \Webauthn\Event\BackupStatusChangedEvent} via its dispatcher; we
 * forward them onto the Flarum bus so application listeners (e.g. an audit
 * logger) can subscribe by class name with no special wiring.
 */
final class IlluminateEventDispatcherAdapter implements EventDispatcherInterface
{
    public function __construct(
        private readonly Dispatcher $bus,
    ) {
    }

    public function dispatch(object $event): object
    {
        $this->bus->dispatch($event);

        return $event;
    }
}
