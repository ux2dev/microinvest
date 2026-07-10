<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Tests\Laravel;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Ux2Dev\Microinvest\Laravel\Facades\Microinvest;
use Ux2Dev\Microinvest\Laravel\MicroinvestServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MicroinvestServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Microinvest' => Microinvest::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('microinvest.default', 'local');
        $app['config']->set('microinvest.connections.local', [
            'base_url' => 'http://127.0.0.1:8700',
            'api_key'  => 'local-key',
            'timeout'  => 30,
        ]);
        $app['config']->set('microinvest.connections.remote', [
            'base_url' => 'https://192.168.1.10:8701',
            'api_key'  => null,
            'timeout'  => 10,
        ]);
    }
}
