<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

use Illuminate\Database\Schema\Builder;

return [
    /**
     * MySQL refuses a plain UNIQUE on a BLOB column unless an explicit key
     * prefix length is given; Laravel's Blueprint does not expose that, so
     * the index is created with a raw statement. SQLite has no such limit
     * and accepts the index without a prefix.
     */
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $driver = $connection->getDriverName();

        $exists = collect($schema->getIndexes('passkeys'))
            ->contains(fn ($idx) => $idx['name'] === 'passkeys_credential_id_unique');
        if ($exists) {
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $connection->statement(
                'ALTER TABLE passkeys ADD UNIQUE KEY passkeys_credential_id_unique (credential_id(255))'
            );
        } else {
            $schema->table('passkeys', function ($table) {
                $table->unique('credential_id', 'passkeys_credential_id_unique');
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->table('passkeys', function ($table) {
            $table->dropUnique('passkeys_credential_id_unique');
        });
    },
];
