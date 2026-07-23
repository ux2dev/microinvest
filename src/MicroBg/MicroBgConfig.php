<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use LogicException;
use Ux2Dev\Microinvest\Exception\ConfigurationException;

/**
 * Validated connection details for one micro.bg external application.
 *
 * Both credentials come from micro.bg: Администриране -> Връзка с ел. магазини
 * -> Регистриране на ново приложение -> Настройки. The secret key never travels
 * on the wire; it only signs the payload.
 */
final readonly class MicroBgConfig
{
    public const DEFAULT_ENTRY_POINT = 'https://micro.bg/ExtApps/ExternalApp/API/';

    public string $entryPoint;

    public function __construct(
        public string $apiId,
        private string $secretKey,
        string $entryPoint = self::DEFAULT_ENTRY_POINT,
        public int $timeout = 30,
    ) {
        if ($apiId === '') {
            throw new ConfigurationException('apiId must not be empty');
        }

        if ($secretKey === '') {
            throw new ConfigurationException('secretKey must not be empty');
        }

        if (! preg_match('~^https?://~i', $entryPoint)) {
            throw new ConfigurationException('entryPoint must start with http:// or https://');
        }

        if ($timeout < 1) {
            throw new ConfigurationException('timeout must be at least 1 second');
        }

        $this->entryPoint = rtrim($entryPoint, '/') . '/';
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return [
            'apiId' => $this->apiId,
            'secretKey' => '[REDACTED]',
            'entryPoint' => $this->entryPoint,
            'timeout' => $this->timeout,
        ];
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        throw new LogicException('MicroBgConfig must not be serialized as it contains a secret key');
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        throw new LogicException('MicroBgConfig must not be unserialized');
    }
}
