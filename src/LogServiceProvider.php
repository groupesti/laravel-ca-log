<?php

declare(strict_types=1);

namespace CA\Log;

use CA\Log\Channel\CaChannelFactory;
use CA\Log\Console\Commands\LogRotateCommand;
use CA\Log\Console\Commands\LogSearchCommand;
use CA\Log\Console\Commands\LogTailCommand;
use CA\Log\Contracts\LogContextInterface;
use CA\Log\Contracts\LogFormatterInterface;
use CA\Log\Formatters\CefFormatter;
use CA\Log\Formatters\JsonFormatter;
use CA\Log\Formatters\SyslogFormatter;
use CA\Log\Services\CaLogger;
use CA\Log\Services\ContextEnricher;
use CA\Log\Services\LogAggregator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ca-log.php', 'ca-log');

        $this->app->singleton(LogContextInterface::class, ContextEnricher::class);

        $this->app->singleton(LogFormatterInterface::class, function ($app): LogFormatterInterface {
            $format = config('ca-log.format', 'json');

            return match ($format) {
                'cef' => $app->make(CefFormatter::class),
                'syslog' => $app->make(SyslogFormatter::class),
                default => $app->make(JsonFormatter::class),
            };
        });

        $this->app->singleton(CaLogger::class, function ($app): CaLogger {
            return new CaLogger(
                formatter: $app->make(LogFormatterInterface::class),
                enricher: $app->make(LogContextInterface::class),
            );
        });

        $this->app->singleton(LogAggregator::class);
        $this->app->singleton(CaChannelFactory::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ca-log.php' => config_path('ca-log.php'),
            ], 'ca-log-config');

            $this->commands([
                LogTailCommand::class,
                LogSearchCommand::class,
                LogRotateCommand::class,
            ]);
        }

        if (config('ca-log.routes.enabled', true)) {
            $this->registerRoutes();
        }

        $this->registerLogChannel();
    }

    private function registerRoutes(): void
    {
        Route::prefix(config('ca-log.routes.prefix', 'api/ca'))
            ->middleware(config('ca-log.routes.middleware', ['api']))
            ->group(__DIR__.'/../routes/api.php');
    }

    private function registerLogChannel(): void
    {
        $this->app->make('log')->extend('ca', function ($app, array $config): \Psr\Log\LoggerInterface {
            return $app->make(CaChannelFactory::class)->create($config);
        });
    }
}
