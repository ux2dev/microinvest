<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;
use Ux2Dev\Microinvest\Exception\TransportException;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * Low-level dispatcher for the micro.bg External App API. Every method is a
 * POST to the same entry point; the method name travels inside the signed
 * payload rather than in the URL.
 */
final class MicroBgTransport
{
    private readonly RequestSigner $signer;

    public function __construct(
        public readonly MicroBgConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
        $this->signer = new RequestSigner($config->apiId, $config->getSecretKey());
    }

    /**
     * @param  array<string, mixed>       $parameters    null values are dropped
     * @param  array<string, mixed>|null  $functionData
     * @return mixed the envelope's `data` node
     */
    public function call(string $functionName, array $parameters = [], ?array $functionData = null): mixed
    {
        $fields = $this->signer->sign([
            'functionName' => $functionName,
            'parameters' => array_filter($parameters, static fn (mixed $v): bool => $v !== null),
            // Cast so an all-null input DTO encodes as {} rather than [].
            'functionData' => $functionData === null ? null : (object) $functionData,
        ]);

        $request = $this->requestFactory->createRequest('POST', $this->config->entryPoint)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream(http_build_query($fields)));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('HTTP transport error: ' . $e->getMessage(), previous: $e);
        }

        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new ApiException("micro.bg returned HTTP {$status}", httpStatus: $status);
        }

        try {
            $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidResponseException(
                "Malformed JSON response for {$functionName}: " . $e->getMessage(),
                previous: $e,
            );
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new InvalidResponseException("Expected a JSON object for {$functionName}");
        }

        return Envelope::unwrap($decoded, $functionName);
    }

    /**
     * Dispatch a call whose data node is a list of rows and hydrate each one.
     *
     * @template T of object
     * @param  array<string, mixed>          $parameters
     * @param  class-string<T&FromMicroBg>   $resultClass
     * @param  array<string, mixed>|null     $functionData
     * @return ResultList<T>
     */
    public function callList(
        string $functionName,
        array $parameters,
        string $resultClass,
        ?array $functionData = null,
    ): ResultList {
        $rows = $this->call($functionName, $parameters, $functionData);

        if ($rows === null) {
            return new ResultList([]);
        }

        if (! is_array($rows) || ! array_is_list($rows)) {
            throw new InvalidResponseException("Expected a list of rows for {$functionName}");
        }

        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidResponseException('Each collection entry must be an object');
            }

            $items[] = $resultClass::fromMicroBg($row);
        }

        return new ResultList($items);
    }

    /**
     * Dispatch a call whose data node is a single object and hydrate it.
     *
     * @template T of object
     * @param  array<string, mixed>          $parameters
     * @param  array<string, mixed>|null     $functionData
     * @param  class-string<T&FromMicroBg>   $resultClass
     * @return T
     */
    public function callOne(
        string $functionName,
        array $parameters,
        ?array $functionData,
        string $resultClass,
    ): object {
        $row = $this->call($functionName, $parameters, $functionData);

        if (! is_array($row) || array_is_list($row)) {
            throw new InvalidResponseException("Expected a single object for {$functionName}");
        }

        return $resultClass::fromMicroBg($row);
    }
}
