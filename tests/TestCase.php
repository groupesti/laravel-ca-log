<?php

declare(strict_types=1);

namespace CA\Log\Tests;

use CA\Log\LogServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LogServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ca-log.path', sys_get_temp_dir().'/ca-log-tests');
        $app['config']->set('ca-log.enrichment.enabled', false);
        $app['config']->set('ca-log.routes.enabled', false);
        $app['config']->set('ca-log.dispatch_critical_events', false);
    }
}
