<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Exception;

/**
 * Thrown when the Microinvest API returns a non-2xx HTTP status. Carries the
 * decoded error envelope so callers can inspect the API `code`/`message`.
 */
final class ApiException extends MicroinvestException
{
    /** @param array<string, mixed> $body */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?int $apiCode = null,
        public readonly ?string $apiMessage = null,
        public readonly array $body = [],
    ) {
        parent::__construct($message);
    }
}
