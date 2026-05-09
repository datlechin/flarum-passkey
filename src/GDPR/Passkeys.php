<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\GDPR;

use Datlechin\Passkey\Model\Passkey;
use Flarum\Gdpr\Data\Type;
use Illuminate\Support\Arr;

/**
 * Adds the user's registered passkeys to a GDPR data export.
 *
 * The credential's public key, transports, and AAGUID identify the credential
 * but never leak secret material, the private key never leaves the
 * authenticator. We still drop the raw `public_key_cose` blob from the export
 * because it is binary and tells the owner nothing they cannot already see in
 * their authenticator. Everything that is meaningful for the data subject
 * (device label, transports, sync state, last-used timestamps) is included.
 *
 * On erasure, every passkey row for the user is deleted. Cascading model
 * boot hooks remove the corresponding `login_providers` entries automatically.
 */
class Passkeys extends Type
{
    public static function piiFields(): array
    {
        return ['last_used_ip', 'user_agent_summary'];
    }

    public static function exportDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-passkey.gdpr.export_description');
    }

    public static function anonymizeDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-passkey.gdpr.anonymize_description');
    }

    public static function deleteDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-passkey.gdpr.delete_description');
    }

    public function export(): ?array
    {
        $rows = Passkey::query()
            ->where('user_id', $this->user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Passkey $p) => Arr::except($p->toArray(), ['public_key_cose', 'credential_id', 'user_id']))
            ->all();

        if ($rows === []) {
            return null;
        }

        return ['passkeys.json' => $this->encodeForExport($rows)];
    }

    public function anonymize(): void
    {
        $this->delete();
    }

    public function delete(): void
    {
        Passkey::query()->where('user_id', $this->user->id)->delete();
    }
}
