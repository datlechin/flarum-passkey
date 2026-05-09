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

use Datlechin\Passkey\Event\PasskeyRevoked;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\Job\SendInformationalEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;

class SendRevokedEmail
{
    public function __construct(
        private readonly Queue $queue,
        private readonly SettingsRepositoryInterface $settings,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function handle(PasskeyRevoked $event): void
    {
        $owner = $event->owner;

        if (! $owner->is_email_confirmed) {
            return;
        }

        $forumTitle = (string) $this->settings->get('forum_title');
        $locale = $owner->getPreference('locale') ?? $this->settings->get('default_locale');
        $locale = is_string($locale) && $locale !== '' ? $locale : null;

        $actorKey = $event->actor->id === $owner->id
            ? 'datlechin-passkey.email.revoked.actor_self'
            : 'datlechin-passkey.email.revoked.actor_admin';

        $subject = (string) $this->translator->trans('datlechin-passkey.email.revoked.subject', [
            'forum' => $forumTitle,
        ], null, $locale);
        $body = (string) $this->translator->trans('datlechin-passkey.email.revoked.body', [
            'username' => $owner->display_name,
            'device' => $event->passkey->device_name,
            'forum' => $forumTitle,
            'actor' => $this->translator->trans($actorKey, [], null, $locale),
        ], null, $locale);

        $this->queue->push(new SendInformationalEmailJob(
            email: $owner->email,
            displayName: $owner->display_name,
            subject: $subject,
            body: $body,
            forumTitle: $forumTitle,
        ));
    }
}
