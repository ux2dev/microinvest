<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use JsonException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;

/**
 * Turns a request payload into the two POST fields micro.bg expects.
 *
 * PDF v1.4 pins the order exactly: json_encode, then base64_encode, then
 * urlencode; the HMAC is taken over the *encoded* string, not over the JSON,
 * and is concatenated onto it. Reordering any step invalidates the signature.
 */
final readonly class RequestSigner
{
    private const HASH_ALGO = 'sha256';

    public function __construct(
        private string $apiId,
        private string $secretKey,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ApiId: string, Request: string}
     */
    public function sign(array $payload): array
    {
        try {
            // PDF v1.4: no JSON_UNESCAPED_UNICODE. The reference implementation
            // uses bare json_encode and the server recomputes the hash over
            // whatever we sent, so the encoding must not drift from it.
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (JsonException $e) {
            throw new InvalidResponseException(
                'Failed to encode micro.bg request payload: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $encoded = urlencode(base64_encode($json));

        return [
            'ApiId' => $this->apiId,
            'Request' => $encoded . hash_hmac(self::HASH_ALGO, $encoded, $this->secretKey),
        ];
    }
}
