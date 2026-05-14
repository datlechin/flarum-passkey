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
use Datlechin\Passkey\Api\Controller\DeletePasskeyController;
use Datlechin\Passkey\Api\Controller\ListPasskeysController;
use Datlechin\Passkey\Api\Controller\UpdatePasskeyController;
use Datlechin\Passkey\Api\Serializer\PasskeySerializer;
use Datlechin\Passkey\Controller\BulkRevokeController;
use Datlechin\Passkey\Controller\LoginController;
use Datlechin\Passkey\Controller\LoginOptionsController;
use Datlechin\Passkey\Controller\RegistrationController;
use Datlechin\Passkey\Controller\RegistrationOptionsController;
use Datlechin\Passkey\Controller\WellKnownController;
use Datlechin\Passkey\Event\PasskeyBulkRevoked;
use Datlechin\Passkey\Event\PasskeyCounterRegression;
use Datlechin\Passkey\Event\PasskeyRevoked;
use Datlechin\Passkey\Listener\SaveGroupPasskeyRequired;
use Datlechin\Passkey\Listener\SendBackupStatusChangedEmail;
use Datlechin\Passkey\Listener\SendBulkRevokedEmail;
use Datlechin\Passkey\Listener\SendCounterRegressionEmail;
use Datlechin\Passkey\Listener\SendRevokedEmail;
use Datlechin\Passkey\Model\Passkey;
use Datlechin\Passkey\Throttle\PasskeyLoginThrottler;
use Flarum\Api\Serializer\GroupSerializer;
use Flarum\Extend;
use Flarum\Group\Event\Saving as GroupSaving;
use Flarum\Group\Group;
use Flarum\User\User;
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
        ->delete('/passkey/bulk-revoke', 'datlechin-passkey.bulk-revoke', BulkRevokeController::class)
        // The 2.x build expressed these three as an Extend\ApiResource; on 1.x
        // they are plain controllers behind the same /api/passkeys URLs, so the
        // frontend store (app.store.find('passkeys'), passkey.save/delete) is
        // unchanged. The plural path keeps clear of the static /passkey/* siblings.
        ->get('/passkeys', 'datlechin-passkey.passkeys.index', ListPasskeysController::class)
        ->patch('/passkeys/{id}', 'datlechin-passkey.passkeys.update', UpdatePasskeyController::class)
        ->delete('/passkeys/{id}', 'datlechin-passkey.passkeys.delete', DeletePasskeyController::class),

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

    (new Extend\Policy())
        ->modelPolicy(Passkey::class, PasskeyPolicy::class),

    (new Extend\ThrottleApi())
        ->set('datlechin-passkey.login', PasskeyLoginThrottler::class),

    (new Extend\Event())
        ->listen(PasskeyRevoked::class, SendRevokedEmail::class)
        ->listen(PasskeyBulkRevoked::class, SendBulkRevokedEmail::class)
        ->listen(PasskeyCounterRegression::class, SendCounterRegressionEmail::class)
        ->listen(BackupStatusChangedEvent::class, SendBackupStatusChangedEmail::class)
        // Persists the `passkeyRequired` group attribute. Group\Event\Saving
        // fires from both the create and edit group handlers, so this single
        // listener covers what the 2.x GroupResource field did for both.
        ->listen(GroupSaving::class, SaveGroupPasskeyRequired::class),

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

    // Read side of the `passkeyRequired` group attribute (the write side is the
    // SaveGroupPasskeyRequired listener above). 2.x did both in one ApiResource
    // field; 1.x splits serialization and persistence across two extenders.
    (new Extend\ApiSerializer(GroupSerializer::class))
        ->attribute('passkeyRequired', fn ($serializer, $group) => (bool) $group->passkey_required),

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
