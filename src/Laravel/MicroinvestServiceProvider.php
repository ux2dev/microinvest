<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Laravel;

use Illuminate\Support\ServiceProvider;

final class MicroinvestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/microinvest.php', 'microinvest');

        $this->app->singleton(MicroinvestManager::class, function ($app) {
            return new MicroinvestManager($app['config']->get('microinvest', []));
        });

        $this->app->alias(MicroinvestManager::class, 'microinvest');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/microinvest.php' => config_path('microinvest.php'),
            ], 'microinvest-config');
        }
    }
}
