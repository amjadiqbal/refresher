<?php

namespace AmjadIqbal\Refresher;

use AmjadIqbal\Refresher\Commands\RestoreCommand;
use AmjadIqbal\Refresher\Commands\SnapshotCommand;
use AmjadIqbal\Refresher\Snapshots\SnapshotDriverFactory;
use Illuminate\Support\ServiceProvider;

class RefresherServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/refresher.php', 'refresher');

        $this->app->singleton(SnapshotDriverFactory::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/refresher.php' => config_path('refresher.php'),
            ], 'refresher-config');

            $this->commands([
                SnapshotCommand::class,
                RestoreCommand::class,
            ]);
        }
    }
}
