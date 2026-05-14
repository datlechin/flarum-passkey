<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Listener;

use Flarum\Group\Event\Saving;
use Illuminate\Support\Arr;

/**
 * Flarum 1.x equivalent of the 2.x `Extend\ApiResource(GroupResource)->fields()`
 * write path for the `passkeyRequired` group attribute.
 *
 * 2.x declared the field as writable inline on the GroupResource. Flarum 1.x
 * keeps a hardcoded attribute whitelist in EditGroupHandler/CreateGroupHandler,
 * so a custom column has to be persisted by listening to the `Group\Event\Saving`
 * event those handlers dispatch (it fires for both create and update).
 */
class SaveGroupPasskeyRequired
{
    public function handle(Saving $event): void
    {
        $attributes = Arr::get($event->data, 'attributes', []);

        if (! array_key_exists('passkeyRequired', $attributes)) {
            return;
        }

        // Faithful port of the 2.x field rule
        //     ->writable(fn ($model, $context) => $context->getActor()->isAdmin())
        // Group\Event\Saving is a public event; core's group-edit handlers
        // happen to be admin-gated today, but that is incidental, not part of
        // the event's contract. Re-asserting here keeps the field's write rule
        // explicit and local — admin-only regardless of how Saving was raised.
        $event->actor->assertAdmin();

        $event->group->passkey_required = (bool) $attributes['passkeyRequired'];
    }
}
