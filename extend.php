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

use Datlechin\Passkey\Access\PasskeyPolicy;
use Datlechin\Passkey\Api\Resource\PasskeyResource;
use Datlechin\Passkey\Controller\BulkRevokeController;
use Datlechin\Passkey\Controller\LoginController;
use Datlechin\Passkey\Controller\LoginOptionsController;
use Datlechin\Passkey\Controller\RegistrationController;
use Datlechin\Passkey\Controller\RegistrationOptionsController;
use Datlechin\Passkey\Controller\WellKnownController;
use Datlechin\Passkey\Event\PasskeyBulkRevoked;
use Datlechin\Passkey\Event\PasskeyCounterRegression;
use Datlechin\Passkey\Event\PasskeyRevoked;
use Datlechin\Passkey\Listener\SendBackupStatusChangedEmail;
use Datlechin\Passkey\Listener\SendBulkRevokedEmail;
use Datlechin\Passkey\Listener\SendCounterRegressionEmail;
use Datlechin\Passkey\Listener\SendRevokedEmail;
use Datlechin\Passkey\Model\Passkey;
use Datlechin\Passkey\Throttle\PasskeyLoginThrottler;
use Flarum\Api\Resource\GroupResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\User\Event\LoggedIn;
use Flarum\User\User;
use Tobyz\JsonApiServer\Context as JsonApiContext;
use Webauthn\Event\BackupStatusChangedEvent;

return [
    (new Extend\ServiceProvider())
        ->register(PasskeyServiceProvider::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->get('/passkey/login-options', 'datlechin-passkey.login-options', LoginOptionsController::class)
        ->post('/passkey/login', 'datlechin-passkey.login', LoginController::class)
        ->get('/passkey/registration-options', 'datlechin-passkey.registration-options', RegistrationOptionsController::class)
        ->post('/passkey/registration', 'datlechin-passkey.registration', RegistrationController::class)
        ->delete('/passkey/bulk-revoke', 'datlechin-passkey.bulk-revoke', BulkRevokeController::class),

    // The W3C related-origins document must be served at /.well-known/webauthn
    // on the apex of the relying party origin. We register it on the forum
    // route group so it shares the canonical host without an /api prefix.
    (new Extend\Routes('forum'))
        ->get('/.well-known/webauthn', 'datlechin-passkey.well-known', WellKnownController::class),

    (new Extend\Frontend('forum'))
        ->content(function (\Flarum\Frontend\Document $document, \Psr\Http\Message\ServerRequestInterface $request) {
            $actor = \Flarum\Http\RequestUtil::getActor($request);
            if ($actor->isGuest()) {
                return;
            }

            $isRequired = $actor->groups()->where('passkey_required', true)->exists();
            if (! $isRequired) {
                return;
            }

            $hasPasskey = Passkey::query()
                ->where('user_id', $actor->id)
                ->exists();

            if ($hasPasskey) {
                return;
            }

            $document->payload['datlechinPasskey'] = ['passkeyRequired' => true];
        }),

    (new Extend\ApiResource(PasskeyResource::class)),

    (new Extend\Policy())
        ->modelPolicy(Passkey::class, PasskeyPolicy::class),

    (new Extend\ThrottleApi())
        ->set('datlechin-passkey.login', PasskeyLoginThrottler::class),

    (new Extend\Event())
        ->listen(PasskeyRevoked::class, SendRevokedEmail::class)
        ->listen(PasskeyBulkRevoked::class, SendBulkRevokedEmail::class)
        ->listen(PasskeyCounterRegression::class, SendCounterRegressionEmail::class)
        ->listen(BackupStatusChangedEvent::class, SendBackupStatusChangedEmail::class),

    (new Extend\Settings())
        ->default('datlechin-passkey.rp_id', '')
        ->default('datlechin-passkey.rp_name', '')
        ->default('datlechin-passkey.related_origins', '')
        ->default('datlechin-passkey.user_verification', 'preferred')
        ->default('datlechin-passkey.attestation', 'none')
        ->default('datlechin-passkey.throttle_per_minute', 10),

    (new Extend\Model(Group::class))
        ->cast('passkey_required', 'bool')
        ->default('passkey_required', false),

    (new Extend\ApiResource(GroupResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('passkeyRequired')
                ->property('passkey_required')
                ->writable(fn ($model, JsonApiContext $context) => $context->getActor()->isAdmin()),
        ]),

    (new Extend\Model(User::class))
        ->hasMany('passkeys', Passkey::class, 'user_id'),

    // When a passkey row is deleted (revoke, GDPR erasure, user delete cascade)
    // also drop the matching login_providers link so the credential id can be
    // re-registered later under a different account if a future user happens to
    // generate the same byte sequence (vanishingly unlikely, but cheap to do).
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', function () {
            return [
                (new \Flarum\Gdpr\Extend\UserData())->addType(GDPR\Passkeys::class),
            ];
        }),
];
