<?php

/*
 * This file is part of datlechin/flarum-passkey.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\Passkey\Exception;

use Flarum\Foundation\KnownError;
use RuntimeException;

class PasskeyVerificationException extends RuntimeException implements KnownError
{
    public function __construct(string $message = 'Passkey verification failed.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getType(): string
    {
        return 'passkey_verification_failed';
    }
}
