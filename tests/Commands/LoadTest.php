<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Mockery as m;

use function Pest\Laravel\assertDatabaseCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotEquals;

function assertSnapshotLoaded($snapshotName)
{
    $nameOfTheLoadedSnapshot = getNameOfLoadedSnapshot();

    assertEquals(
        $snapshotName,
        $nameOfTheLoadedSnapshot,
        "Failed to assert that `{$snapshotName}` is loaded. Current snapshot: `{$nameOfTheLoadedSnapshot}`"
    );
}

function assertSnapshotNotLoaded($snapshotName): void
{
    assertNotEquals(
        $snapshotName,
        getNameOfLoadedSnapshot(),
        "Failed to assert that `{$snapshotName}` was not loaded."
    );
}

function getNameOfLoadedSnapshot(): string
{
    $result = DB::select('select `name` from models;');

    return count($result) ? $result[0]->name : '';
}

beforeEach(function () {
    $this->command = m::mock('Spatie\DbSnapshots\Commands\Load[choice]');

    $this->app->bind('command.snapshot:load', function () {
        return $this->command;
    });
});

it('can load a snapshot', function () {
    assertSnapshotNotLoaded('snapshot2');

    $this->command
        ->shouldReceive('choice')
        ->once()
        ->andReturn('snapshot2');

    Artisan::call('snapshot:load');

    assertSnapshotLoaded('snapshot2');
});

it('can load a snapshot via streaming', function () {
    assertSnapshotNotLoaded('snapshot2');

    $this->command
        ->shouldReceive('choice')
        ->once()
        ->andReturn('snapshot2');

    Artisan::call('snapshot:load', [
        '--stream' => true,
    ]);

    assertSnapshotLoaded('snapshot2');
});

it('can load a compressed snapshot via streaming', function () {
    assertSnapshotNotLoaded('snapshot4');

    $this->command
        ->shouldReceive('choice')
        ->once()
        ->andReturn('snapshot4');

    Artisan::call('snapshot:load', [
        '--stream' => true,
    ]);

    assertSnapshotLoaded('snapshot4');
});

it('drops tables when loading a snapshot', function () {
    DB::insert('insert into `users` (`id`, `name`) values (1, "test")');

    $this->command
        ->shouldReceive('choice')
        ->once()
        ->andReturn('snapshot2');

    Artisan::call('snapshot:load');

    assertTableNotExists('users');
});

it('can load a snapshot without dropping existing tables', function () {
    DB::insert('insert into `users` (`id`, `name`) values (1, "test")');

    $this->command
        ->shouldReceive('choice')
        ->once()
        ->andReturn('snapshot2');

    Artisan::call('snapshot:load', ['--drop-tables' => 0]);

    assertDatabaseCount('users', 1);
});

it('can load a snapshot with a given name', function () {
    assertSnapshotNotLoaded('snapshot2');

    Artisan::call('snapshot:load', ['name' => 'snapshot2']);

    assertSnapshotLoaded('snapshot2');
});

it('can load the latest snapshot', function () {
    assertSnapshotNotLoaded('snapshot4');

    Artisan::call('snapshot:load', ['--latest' => true]);

    assertSnapshotLoaded('snapshot4');
});

it('can load a snapshot with connection option', function () {
    assertSnapshotNotLoaded('snapshot2');

    Artisan::call('snapshot:load', ['name' => 'snapshot2', '--connection' => 'testing']);

    assertSnapshotLoaded('snapshot2');
});

it('can load a compressed snapshot', function () {
    assertSnapshotNotLoaded('snapshot4');

    Artisan::call('snapshot:load', ['name' => 'snapshot4']);

    assertSnapshotLoaded('snapshot4');
});
