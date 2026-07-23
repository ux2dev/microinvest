<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Exception\InvalidResponseException;

/**
 * Reads the envelope every micro.bg method returns.
 *
 * PDF v1.4 is inconsistent here: the class_ApiMicroBg description promises a
 * `success` property while every worked example returns `status`. Both are
 * accepted. The HTTP status is always 200, so failure only surfaces in here.
 */
final class Envelope
{
    /**
     * @param  array<string, mixed>  $decoded
     * @return mixed the `data` node, or null when the method reports none
     */
    public static function unwrap(array $decoded, string $functionName): mixed
    {
        $ok = $decoded['status'] ?? $decoded['success'] ?? null;

        if ($ok === null) {
            throw new InvalidResponseException(
                "micro.bg response for {$functionName} has neither a status nor a success flag",
            );
        }

        if (! $ok) {
            $message = self::errorMessage($decoded);

            throw new ApiException(
                $message ?? "micro.bg rejected {$functionName}",
                httpStatus: 200,
                apiMessage: $message,
                body: $decoded,
            );
        }

        return $decoded['data'] ?? null;
    }

    /** @param array<string, mixed> $decoded */
    private static function errorMessage(array $decoded): ?string
    {
        $errors = $decoded['errors'] ?? null;

        if (is_string($errors)) {
            return $errors === '' ? null : $errors;
        }

        if (! is_array($errors) || $errors === []) {
            return null;
        }

        $parts = array_map(
            static fn (mixed $e): string => is_string($e) ? $e : (string) json_encode($e, JSON_UNESCAPED_UNICODE),
            $errors,
        );

        $message = implode('; ', array_filter($parts, static fn (string $p): bool => $p !== ''));

        return $message === '' ? null : $message;
    }
}
