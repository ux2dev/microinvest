<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\MicroBg\Resources\Items;
use Ux2Dev\Microinvest\MicroBg\Resources\Partners;

/**
 * Client for the micro.bg External App API — the hosted Microinvest service.
 *
 * One instance per registered external application.
 */
final class MicroBgClient
{
    public readonly MicroBgTransport $transport;

    private ?Partners $partners = null;
    private ?Items $items = null;

    public function __construct(
        MicroBgConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->transport = new MicroBgTransport($config, $httpClient, $requestFactory, $streamFactory);
    }

    public function partners(): Partners
    {
        return $this->partners ??= new Partners($this->transport);
    }

    public function items(): Items
    {
        return $this->items ??= new Items($this->transport);
    }
}
