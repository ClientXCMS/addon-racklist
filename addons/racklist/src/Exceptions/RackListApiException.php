<?php

declare(strict_types=1);

namespace App\Addons\RackList\Exceptions;

use RuntimeException;

/**
 * Error raised by the RackList API or before calling it.
 * errorCode mirrors the stable "code" of the RFC 9457 problem body.
 */
final class RackListApiException extends RuntimeException
{
    public const MISSING_KEY = 'MISSING_KEY';

    public const CONNECTION_FAILED = 'CONNECTION_FAILED';

    public const TOO_MANY_PRODUCTS = 'TOO_MANY_PRODUCTS';

    public const EMPTY_CATALOGUE = 'EMPTY_CATALOGUE';

    public function __construct(
        string $message,
        public readonly string $errorCode = 'UNKNOWN',
        public readonly int $status = 0,
        public readonly array $details = [],
        public readonly ?int $retryAfter = null,
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message, $status);
    }

    public function isRateLimited(): bool
    {
        return $this->status === 429;
    }
}
