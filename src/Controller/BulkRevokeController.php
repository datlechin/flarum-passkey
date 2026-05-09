<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Controller;

use Datlechin\Passkey\Event\PasskeyBulkRevoked;
use Datlechin\Passkey\Model\Passkey;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\LoginProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Wipes every passkey on the authenticated actor's account in one transaction.
 * Lives outside PasskeyResource because the variable `/passkeys/{id}` route
 * shadows static siblings. Uses mass-delete to skip the Eloquent `deleted`
 * boot event (which would queue one PasskeyRevoked email per credential).
 */
class BulkRevokeController implements RequestHandlerInterface
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly ConnectionInterface $db,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        if ($actor->isGuest()) {
            throw new NotAuthenticatedException;
        }

        $count = $this->db->transaction(function () use ($actor): int {
            LoginProvider::where('user_id', $actor->id)
                ->where('provider', 'passkey')
                ->delete();

            return (int) Passkey::query()->where('user_id', $actor->id)->delete();
        });

        if ($count === 0) {
            return new EmptyResponse(204);
        }

        $this->events->dispatch(new PasskeyBulkRevoked(
            owner: $actor,
            actor: $actor,
            count: $count,
        ));

        return new EmptyResponse(204);
    }
}
