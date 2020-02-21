<?php

namespace Spatie\DbSnapshots;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\ServiceProvider;
use Spatie\DbSnapshots\Commands\Cleanup;
use Spatie\DbSnapshots\Commands\Create;
use Spatie\DbSnapshots\Commands\Delete;
use Spatie\DbSnapshots\Commands\ListSnapshots;
use Spatie\DbSnapshots\Commands\Load;

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

        $this->app->bind(SnapshotRepository::class, function () {
            $diskName = config('db-snapshots.disk');

            $disk = app(Factory::class)->disk($diskName);

            return new SnapshotRepository($disk);
        });

        $this->app->bind('command.snapshot:create', Create::class);
        $this->app->bind('command.snapshot:load', Load::class);
        $this->app->bind('command.snapshot:delete', Delete::class);
        $this->app->bind('command.snapshot:list', ListSnapshots::class);
        $this->app->bind('command.snapshot:cleanup', Cleanup::class);

        $this->commands([
            'command.snapshot:create',
            'command.snapshot:load',
            'command.snapshot:delete',
            'command.snapshot:list',
            'command.snapshot:cleanup',
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/db-snapshots.php', 'db-snapshots');
    }
}
