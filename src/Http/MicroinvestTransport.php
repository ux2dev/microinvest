<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Http;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\Config\MicroinvestConfig;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\Exception\TransportException;

/**
 * Low-level HTTP transport for the Microinvest Warehouse Pro REST API.
 * Resources build the path, query and (optional) body and call one of the
 * public helpers.
 */
final class MicroinvestTransport
{
    public function __construct(
        public readonly MicroinvestConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Dispatch a request and return a normalized envelope.
     *
     * @param  array<string, mixed>  $query  null values are dropped
     * @param  array<mixed>|null     $body
     * @return array{data: mixed, httpStatus: int, currentPage: ?int, totalPages: ?int}
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        $url = $this->config->baseUrl . $path;

        $query = array_filter($query, static fn ($v) => $v !== null);
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $httpRequest = $this->requestFactory->createRequest($method, $url)
            ->withHeader('Accept', 'application/json');

        if ($this->config->getApiKey() !== null) {
            $httpRequest = $httpRequest->withHeader('X-API-Key', $this->config->getApiKey());
        }

        if ($body !== null) {
            try {
                $json = json_encode(
                    $body,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
                );
            } catch (JsonException $e) {
                throw new InvalidResponseException('Failed to encode request body: ' . $e->getMessage(), previous: $e);
            }

            $httpRequest = $httpRequest
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withBody($this->streamFactory->createStream($json));
        }

        try {
            $response = $this->httpClient->sendRequest($httpRequest);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('HTTP transport error: ' . $e->getMessage(), previous: $e);
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        $decoded = null;
        if ($raw !== '') {
            try {
                $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new InvalidResponseException(
                    "Malformed JSON response (HTTP {$status}): " . $e->getMessage(),
                    previous: $e,
                );
            }
        }

        if ($status < 200 || $status >= 300) {
            $this->throwApiException($status, is_array($decoded) ? $decoded : []);
        }

        if (! is_array($decoded)) {
            throw new InvalidResponseException(
                "Expected a JSON array or object (HTTP {$status}), got " . gettype($decoded),
            );
        }

        return [
            'data'        => $decoded,
            'httpStatus'  => $status,
            'currentPage' => $this->intHeader($response, 'X-CurrentPage'),
            'totalPages'  => $this->intHeader($response, 'X-TotalPages'),
        ];
    }

    /**
     * Dispatch a request whose body is a JSON array of rows and hydrate each
     * into $resultClass.
     *
     * @template T of object
     * @param  array<string, mixed>  $query
     * @param  class-string<T>       $resultClass  must expose static fromArray(array): self
     * @param  array<mixed>|null     $body
     * @return ResultList<T>
     */
    public function requestList(
        string $method,
        string $path,
        array $query,
        string $resultClass,
        ?array $body = null,
    ): ResultList {
        $env = $this->request($method, $path, $query, $body);

        if (! array_is_list($env['data'])) {
            throw new InvalidResponseException("Expected a JSON array for {$path}");
        }

        $items = [];
        foreach ($env['data'] as $row) {
            if (! is_array($row)) {
                throw new InvalidResponseException('Each collection entry must be an object');
            }
            $items[] = $resultClass::fromArray($row);
        }

        return new ResultList(
            items: $items,
            currentPage: $env['currentPage'],
            totalPages: $env['totalPages'],
        );
    }

    /**
     * Dispatch a request that returns a single JSON object and hydrate it.
     *
     * @template T of object
     * @param  array<string, mixed>  $query
     * @param  array<mixed>|null     $body
     * @param  class-string<T>       $resultClass
     * @return T
     */
    public function requestOne(
        string $method,
        string $path,
        array $query,
        ?array $body,
        string $resultClass,
    ): object {
        $env = $this->request($method, $path, $query, $body);

        if (array_is_list($env['data'])) {
            throw new InvalidResponseException("Expected a JSON object for {$path}, got an array");
        }

        return $resultClass::fromArray($env['data']);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function throwApiException(int $status, array $body): never
    {
        $node = isset($body['error']) && is_array($body['error']) ? $body['error'] : $body;

        $apiCode = isset($node['code']) ? (int) $node['code'] : null;
        $apiMessage = isset($node['message']) && is_string($node['message']) ? $node['message'] : null;

        throw new ApiException(
            $apiMessage ?? "Microinvest API returned HTTP {$status}",
            httpStatus: $status,
            apiCode: $apiCode,
            apiMessage: $apiMessage,
            body: $body,
        );
    }

    private function intHeader(ResponseInterface $response, string $name): ?int
    {
        $value = $response->getHeaderLine($name);

        return $value === '' ? null : (int) $value;
    }
}
