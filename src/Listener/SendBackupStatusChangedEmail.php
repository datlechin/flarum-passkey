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

use Datlechin\Passkey\Model\Passkey;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\Job\SendInformationalEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Queue\Queue;
use Webauthn\Event\BackupStatusChangedEvent;

/**
 * web-auth-lib emits this PSR-14 event; IlluminateEventDispatcherAdapter
 * forwards it to the Flarum bus so we can subscribe by class.
 */
class SendBackupStatusChangedEmail
{
    public function __construct(
        private readonly Queue $queue,
        private readonly SettingsRepositoryInterface $settings,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function handle(BackupStatusChangedEvent $event): void
    {
        $passkey = Passkey::query()
            ->where('credential_id', $event->credentialRecord->publicKeyCredentialId)
            ->with('user')
            ->first();

        if ($passkey === null || $passkey->user === null) {
            return;
        }

        $owner = $passkey->user;
        if (! $owner->is_email_confirmed) {
            return;
        }

        $forumTitle = (string) $this->settings->get('forum_title');
        $locale = $owner->getPreference('locale') ?? $this->settings->get('default_locale');
        $locale = is_string($locale) && $locale !== '' ? $locale : null;

        $stateKey = $event->newValue
            ? 'datlechin-passkey.email.backup_state.synced'
            : 'datlechin-passkey.email.backup_state.unsynced';

        $subject = (string) $this->translator->trans('datlechin-passkey.email.backup_state.subject', [
            'forum' => $forumTitle,
        ], null, $locale);
        $body = (string) $this->translator->trans('datlechin-passkey.email.backup_state.body', [
            'username' => $owner->display_name,
            'device' => $passkey->device_name,
            'state' => $this->translator->trans($stateKey, [], null, $locale),
            'forum' => $forumTitle,
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
