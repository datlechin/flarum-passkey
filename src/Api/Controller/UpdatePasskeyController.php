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
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tobscure\JsonApi\Document;

/**
 * Flarum 1.x equivalent of the 2.x PasskeyResource Endpoint\Update.
 *
 * `deviceName` is the only writable field. 2.x expressed its constraints
 * declaratively (Schema\Str ->writable() ->minLength(1) ->maxLength(64));
 * here the same rule has to be enforced imperatively — see applyDeviceName().
 */
class UpdatePasskeyController extends AbstractShowController
{
    public $serializer = PasskeySerializer::class;

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $passkey = Passkey::findOrFail($id);

        // PasskeyPolicy::update() — only the credential owner.
        $actor->assertCan('update', $passkey);

        $attributes = Arr::get($request->getParsedBody(), 'data.attributes', []);

        if (array_key_exists('deviceName', $attributes)) {
            $this->applyDeviceName($passkey, $attributes['deviceName']);
        }

        $passkey->save();

        return $passkey;
    }

    /**
     * Validate and apply the incoming `deviceName` to the passkey.
     *
     * Faithful port of the 2.x resource's declarative rule
     *     Schema\Str::make('deviceName')->writable()->minLength(1)->maxLength(64)
     * which *rejects* out-of-range input with a 422. Flarum 1.x has no
     * declarative equivalent, so the same contract is enforced here by throwing
     * a ValidationException (the credential owner gets a field error, exactly
     * as core's own validators do) rather than silently coercing the value —
     * coercion would change the API's observed behaviour from what 2.x ships.
     *
     * The parameter is `mixed` because the JSON:API body is untrusted: a
     * non-string `deviceName` (array, number, null) is normalised to '' so it
     * falls into the same rejection path instead of triggering a cast warning.
     */
    private function applyDeviceName(Passkey $passkey, mixed $deviceName): void
    {
        $deviceName = is_string($deviceName) ? trim($deviceName) : '';

        if ($deviceName === '' || mb_strlen($deviceName) > 64) {
            throw new ValidationException([
                'deviceName' => $this->translator->trans('datlechin-passkey.forum.settings.invalid_device_name'),
            ]);
        }

        $passkey->device_name = $deviceName;
    }
}
