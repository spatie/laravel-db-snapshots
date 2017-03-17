<?php

namespace Spatie\DbSnapshots;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\ServiceProvider;

class DbSnapshotsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/db-snapshots.php' => config_path('db-snapshots.php'),
            ], 'config');
        }

        $this->app->bind(SnapshotRepository::class, function() {
            $diskName = config('db-snapshots.disk');

            $disk = app(Factory::class)->disk($diskName);

            return new SnapshotRepository($disk);
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/db-snapshots.php', 'db-snapshots');
    }
}
