<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Api\Serializer;

use Datlechin\Passkey\Model\Passkey;
use Flarum\Api\Serializer\AbstractSerializer;
use InvalidArgumentException;

/**
 * Flarum 1.x equivalent of the 2.x PasskeyResource fields().
 *
 * The attribute keys (and the RFC3339 date format from formatDate()) match
 * what the frontend `Passkey` model expects, so the store-driven components
 * are unaffected by the 1.x/2.x API-layer difference.
 */
class PasskeySerializer extends AbstractSerializer
{
    protected $type = 'passkeys';

    /**
     * {@inheritdoc}
     *
     * @param Passkey $passkey
     */
    protected function getDefaultAttributes($passkey)
    {
        if (! ($passkey instanceof Passkey)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Passkey::class
            );
        }

        return [
            'deviceName' => $passkey->device_name,
            'aaguid' => $passkey->aaguid,
            'authenticatorName' => $passkey->authenticator_name,
            'transports' => $passkey->transports,
            'backupEligible' => $passkey->backup_eligible,
            'backupState' => $passkey->backup_state,
            'createdAt' => $this->formatDate($passkey->created_at),
            'lastUsedAt' => $this->formatDate($passkey->last_used_at),
            'userAgentSummary' => $passkey->user_agent_summary,
        ];
    }
}
