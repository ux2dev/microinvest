<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Laravel;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\Config\MicroinvestConfig;
use Ux2Dev\Microinvest\Exception\ConfigurationException;
use Ux2Dev\Microinvest\Microinvest;

/**
 * Laravel integration. Resolves connection configuration from
 * `config/microinvest.php` and exposes a lazy, cached {@see Microinvest}
 * instance per connection (each Microinvest install is its own host).
 *
 * Supports immutable connection switching via `->connection('foo')` which
 * clones the manager with the target connection active.
 */
final class MicroinvestManager
{
    /** @var array<string, Microinvest> */
    private array $instances = [];

    private string $currentConnection;

    public function __construct(
        /** @var array<string, mixed> */
        private readonly array $config,
        private readonly ?ClientInterface $httpClient = null,
        private readonly ?RequestFactoryInterface $requestFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->currentConnection = (string) ($config['default'] ?? 'local');
    }

    public function connection(string $name): self
    {
        $clone = clone $this;
        $clone->currentConnection = $name;
        return $clone;
    }

    public function currentConnection(): string
    {
        return $this->currentConnection;
    }

    public function client(): Microinvest
    {
        return $this->instances[$this->currentConnection] ??= $this->build($this->currentConnection);
    }

    /**
     * Forward any Microinvest resource accessor (e.g. items()) straight through.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->client()->{$method}(...$arguments);
    }

    private function build(string $connection): Microinvest
    {
        $connections = (array) ($this->config['connections'] ?? []);

        if (! isset($connections[$connection]) || ! is_array($connections[$connection])) {
            throw new ConfigurationException("Microinvest connection \"{$connection}\" is not configured");
        }

        $c = $connections[$connection];

        $apiKey = $c['api_key'] ?? null;

        $config = new MicroinvestConfig(
            baseUrl: (string) ($c['base_url'] ?? ''),
            apiKey:  $apiKey !== null && $apiKey !== '' ? (string) $apiKey : null,
            timeout: (int) ($c['timeout'] ?? 30),
        );

        $factory = new HttpFactory();

        return new Microinvest(
            $config,
            $this->httpClient ?? new Client(['timeout' => $config->timeout]),
            $this->requestFactory ?? $factory,
            $this->streamFactory ?? $factory,
        );
    }
}
