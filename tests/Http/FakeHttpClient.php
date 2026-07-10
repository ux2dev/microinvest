<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Tests\Http;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $received = [];

    public function __construct(
        private readonly ?ResponseInterface $response = null,
        private readonly ?Throwable $exception = null,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->received[] = $request;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response ?? new Response(200, [], '[]');
    }

    /**
     * @param  array<mixed>          $body
     * @param  array<string, string> $headers
     */
    public static function withJson(array $body, int $status = 200, array $headers = []): self
    {
        return new self(new Response(
            status: $status,
            headers: $headers + ['Content-Type' => 'application/json'],
            body: json_encode($body, JSON_UNESCAPED_UNICODE),
        ));
    }

    /** @param array<string, string> $headers */
    public static function withRaw(string $body, int $status = 200, array $headers = []): self
    {
        return new self(new Response($status, $headers + ['Content-Type' => 'application/json'], $body));
    }

    public static function throwing(Throwable $e): self
    {
        return new self(exception: $e);
    }

    public static function factory(): HttpFactory
    {
        return new HttpFactory();
    }

    public function lastRequest(): RequestInterface
    {
        return $this->received[array_key_last($this->received)];
    }
}
