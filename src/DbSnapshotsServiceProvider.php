<?php

namespace Spatie\DbSnapshots;

use Illuminate\Contracts\Filesystem\Factory;
use Spatie\DbSnapshots\Commands\Cleanup;
use Spatie\DbSnapshots\Commands\Create;
use Spatie\DbSnapshots\Commands\Delete;
use Spatie\DbSnapshots\Commands\ListSnapshots;
use Spatie\DbSnapshots\Commands\Load;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DbSnapshotsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-db-snapshots')
            ->hasConfigFile()
            ->hasCommands([
                'command.snapshot:create',
                'command.snapshot:load',
                'command.snapshot:delete',
                'command.snapshot:list',
                'command.snapshot:cleanup',
            ]);
    }

    public function bootingPackage()
    {
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
    }
}
