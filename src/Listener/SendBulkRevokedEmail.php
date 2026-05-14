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

use Datlechin\Passkey\Event\PasskeyBulkRevoked;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendBulkRevokedEmail
{
    public function __construct(
        private readonly Queue $queue,
        private readonly SettingsRepositoryInterface $settings,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function handle(PasskeyBulkRevoked $event): void
    {
        $owner = $event->owner;
        if (! $owner->is_email_confirmed) {
            return;
        }

        $forumTitle = (string) $this->settings->get('forum_title');
        $locale = $owner->getPreference('locale') ?? $this->settings->get('default_locale');
        $locale = is_string($locale) && $locale !== '' ? $locale : null;

        $actorKey = $event->actor->id === $owner->id
            ? 'datlechin-passkey.email.bulk_revoked.actor_self'
            : 'datlechin-passkey.email.bulk_revoked.actor_admin';

        $subject = (string) $this->translator->trans('datlechin-passkey.email.bulk_revoked.subject', [
            'forum' => $forumTitle,
        ], null, $locale);
        $body = (string) $this->translator->trans('datlechin-passkey.email.bulk_revoked.body', [
            'username' => $owner->display_name,
            'count' => $event->count,
            'forum' => $forumTitle,
            'actor' => $this->translator->trans($actorKey, [], null, $locale),
        ], null, $locale);

        // Flarum 1.x has no SendInformationalEmailJob (the HTML-templated job is
        // 2.x-only); SendRawEmailJob sends the already-translated plain-text body.
        $this->queue->push(new SendRawEmailJob($owner->email, $subject, $body));
    }
}
