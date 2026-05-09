<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // base64url is ASCII by spec. On MySQL/MariaDB we narrow the credential_id
        // column to ASCII with a binary (case-sensitive) collation so its unique
        // index sits comfortably under InnoDB's 3072-byte key limit; at utf8mb4 a
        // VARCHAR(1024) index would need 4096 bytes and be rejected. Postgres has
        // no comparable limit and SQLite's default BINARY collation is already
        // case-sensitive, so they get plain VARCHAR.
        $isMysql = in_array(
            $schema->getConnection()->getDriverName(),
            ['mysql', 'mariadb'],
            true
        );

        $schema->create('passkeys', function (Blueprint $table) use ($isMysql) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_id');

            $credentialId = $table->string('credential_id', 1024);
            if ($isMysql) {
                $credentialId->charset('ascii')->collation('ascii_bin');
            }

            $table->text('public_key_cose');

            // 32-bit unsigned counter from the authenticator. We store as unsigned 64-bit
            // to leave headroom for any future encoding changes.
            $table->unsignedBigInteger('signature_count')->default(0);

            // List of WebAuthn transports declared by the authenticator. Stored as JSON
            // so we can preserve exact ordering reported by the platform.
            $table->json('transports');

            // AAGUID identifies the authenticator make/model (RFC 4122 UUID, all-zero for
            // self-attested or anonymized credentials).
            $table->uuid('aaguid')->nullable();

            $table->string('attestation_format', 32)->default('none');

            // FIDO Level 3 backup flags from the authenticator data.
            $table->boolean('backup_eligible')->default(false);
            $table->boolean('backup_state')->default(false);

            // True once the authenticator has performed user verification at least once
            // for this credential (lets us upgrade from UV=preferred to required later).
            $table->boolean('uv_initialized')->default(false);

            $table->string('device_name', 64);
            $table->string('user_agent_summary', 255)->nullable();

            $table->timestamp('last_used_at')->nullable();
            $table->ipAddress('last_used_ip')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->unique('credential_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    },
    'down' => function (Builder $schema) {
        $schema->dropIfExists('passkeys');
    },
];
