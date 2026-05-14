<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Api\Controller;

use Datlechin\Passkey\Event\PasskeyRevoked;
use Datlechin\Passkey\Model\Passkey;
use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Flarum 1.x equivalent of the 2.x PasskeyResource Endpoint\Delete.
 *
 * The matching `login_providers` row is cleaned up by the Passkey model's
 * `deleted` boot hook, so this controller only has to authorize the actor and
 * announce the revocation before the row goes away.
 */
class DeletePasskeyController extends AbstractDeleteController
{
    public function __construct(
        private readonly Dispatcher $events,
    ) {
    }

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $passkey = Passkey::findOrFail($id);

        // PasskeyPolicy::delete() — owner, or any admin (support cases).
        $actor->assertCan('delete', $passkey);

        // Dispatch before delete() so the event still carries a fully
        // populated model (and so listeners can read the owner relation).
        $this->events->dispatch(new PasskeyRevoked(
            owner: $passkey->user,
            actor: $actor,
            passkey: $passkey,
        ));

        $passkey->delete();
    }
}
