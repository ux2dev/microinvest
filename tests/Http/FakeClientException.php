<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Tests\Http;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * A concrete PSR-18 client exception for exercising the transport's error
 * handling in tests.
 */
final class FakeClientException extends RuntimeException implements ClientExceptionInterface
{
}
