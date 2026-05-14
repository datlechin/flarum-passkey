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

use Datlechin\Passkey\Api\Serializer\PasskeySerializer;
use Datlechin\Passkey\Model\Passkey;
use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

/**
 * Flarum 1.x equivalent of the 2.x PasskeyResource Endpoint\Index.
 *
 * `authenticated()`         -> $actor->assertRegistered()
 * `paginate(50, 200)`       -> $limit / $maxLimit
 * `defaultSort('-createdAt')` -> $sort
 * `scope()`                 -> the actor visibility filter in data()
 *
 * The `$limit`/`$maxLimit` cap is kept, but no JSON:API pagination links are
 * emitted (the 2.x resource layer added them automatically). A passkey list is
 * a handful of rows per user; the frontend loads it once and never paginates,
 * so the links would be dead weight. Add `$document->addPaginationLinks(...)`
 * here if a future surface ever needs to page through credentials.
 */
class ListPasskeysController extends AbstractListController
{
    public $serializer = PasskeySerializer::class;

    public $sortFields = ['createdAt', 'lastUsedAt', 'deviceName'];

    public $sort = ['createdAt' => 'desc'];

    public $limit = 50;

    public $maxLimit = 200;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $sort = $this->extractSort($request);
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $query = Passkey::query();

        // Admins see every passkey for support flows; everyone else is pinned
        // to their own credentials.
        if (! $actor->isAdmin()) {
            $query->where('user_id', $actor->id);
        }

        foreach ((array) $sort as $field => $order) {
            $query->orderBy(Str::snake($field), $order);
        }

        return $query->skip($offset)->take($limit)->get();
    }
}
