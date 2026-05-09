<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Api\Resource;

use Datlechin\Passkey\Event\PasskeyRevoked;
use Datlechin\Passkey\Model\Passkey;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Http\RequestUtil;
use Flarum\User\LoginProvider;
use Illuminate\Database\Eloquent\Builder;
use Tobyz\JsonApiServer\Context as JsonApiContext;

class PasskeyResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'passkeys';
    }

    public function model(): string
    {
        return Passkey::class;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->paginate(50, 200)
                ->defaultSort('-createdAt'),

            Endpoint\Update::make()
                ->authenticated()
                ->can('update'),

            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete')
                ->beforeSerialization(function (Context $context, $data) {
                    /** @var Passkey $passkey */
                    $passkey = $context->model;

                    LoginProvider::where('provider', 'passkey')
                        ->where('identifier', $passkey->getProviderIdentifier())
                        ->delete();

                    $this->events->dispatch(new PasskeyRevoked(
                        owner: $passkey->user,
                        actor: RequestUtil::getActor($context->request),
                        passkey: $passkey,
                    ));
                }),

        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('deviceName')
                ->property('device_name')
                ->writable()
                ->minLength(1)
                ->maxLength(64),

            Schema\Str::make('aaguid'),

            Schema\Str::make('authenticatorName')
                ->property('authenticator_name'),

            Schema\Arr::make('transports'),

            Schema\Boolean::make('backupEligible')
                ->property('backup_eligible'),

            Schema\Boolean::make('backupState')
                ->property('backup_state'),

            Schema\DateTime::make('createdAt')
                ->property('created_at'),

            Schema\DateTime::make('lastUsedAt')
                ->property('last_used_at'),

            Schema\Str::make('userAgentSummary')
                ->property('user_agent_summary'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
            SortColumn::make('lastUsedAt'),
            SortColumn::make('deviceName'),
        ];
    }

    public function scope(Builder $query, JsonApiContext $context): void
    {
        /** @var Context $context */
        $actor = $context->getActor();

        if ($actor->isGuest()) {
            $query->whereRaw('1 = 0');

            return;
        }

        // Admins see every passkey for support flows.
        if ($actor->isAdmin()) {
            return;
        }

        $query->where('user_id', $actor->id);
    }
}
