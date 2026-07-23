<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro;

use Ux2Dev\Microinvest\Exception\ConfigurationException;

/**
 * Validated value object holding the connection details for one Microinvest
 * Utility Center host. The API key is optional; when omitted the API is
 * reached anonymously.
 */
final readonly class WarehouseProConfig
{
    public string $baseUrl;

    public function __construct(
        string $baseUrl,
        private ?string $apiKey = null,
        public int $timeout = 30,
    ) {
        if ($baseUrl === '') {
            throw new ConfigurationException('baseUrl must not be empty');
        }

        if (! preg_match('~^https?://~i', $baseUrl)) {
            throw new ConfigurationException('baseUrl must start with http:// or https://');
        }

        if ($apiKey !== null && $apiKey === '') {
            throw new ConfigurationException('apiKey must not be an empty string; pass null for anonymous access');
        }

        if ($timeout < 1) {
            throw new ConfigurationException('timeout must be at least 1 second');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'baseUrl' => $this->baseUrl,
            'apiKey' => $this->apiKey !== null ? '[REDACTED]' : null,
            'timeout' => $this->timeout,
        ];
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        throw new ConfigurationException(
            'WarehouseProConfig must not be serialized as it may contain an API key'
        );
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        throw new ConfigurationException('WarehouseProConfig must not be unserialized');
    }
}
