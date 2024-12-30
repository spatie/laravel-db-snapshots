<?php

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery as m;

use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Snapshot;
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

it('throws an error when snapshot does not exist', function () {
    $this->expectException(Exception::class);

    $disk = m::mock(FilesystemAdapter::class);
    $disk->shouldReceive('exists')
        ->with('nonexistent.sql')
        ->andReturn(false);

    $snapshot = new Snapshot($disk, 'nonexistent.sql');
    $snapshot->load();
});

it('throws an error for invalid SQL in snapshot', function () {
    $disk = m::mock(FilesystemAdapter::class);
    $disk->shouldReceive('get')
        ->andReturn("INVALID SQL;\n");

    $snapshot = new Snapshot($disk, 'invalid.sql');

    $this->expectException(Exception::class);
    $snapshot->load();
});

it('deletes the snapshot and triggers event', function () {
    Event::fake();

    $disk = m::mock(FilesystemAdapter::class);
    $disk->shouldReceive('delete')
        ->once()
        ->with('snapshot.sql')
        ->andReturn(true);

    $snapshot = new Snapshot($disk, 'snapshot.sql');
    $snapshot->delete();

    Event::assertDispatched(DeletedSnapshot::class, function ($event) use ($snapshot) {
        return $event->fileName === $snapshot->fileName && $event->disk === $snapshot->disk;
    });
});

it('returns the correct size of the snapshot', function () {
    $disk = m::mock(FilesystemAdapter::class);
    $disk->shouldReceive('size')
        ->andReturn(2048);

    $snapshot = new Snapshot($disk, 'snapshot.sql');

    assertEquals(2048, $snapshot->size());
});

it('returns the correct creation date of the snapshot', function () {
    $timestamp = Carbon::now()->timestamp;

    $disk = m::mock(FilesystemAdapter::class);
    $disk->shouldReceive('lastModified')
        ->andReturn($timestamp);

    $snapshot = new Snapshot($disk, 'snapshot.sql');

    assertEquals(Carbon::createFromTimestamp($timestamp), $snapshot->createdAt());
});

it('handles empty snapshots gracefully', function () {
    $disk = m::mock(FilesystemAdapter::class);
    $disk->shouldReceive('get')
        ->andReturn("");

    $snapshot = new Snapshot($disk, 'empty.sql');

    $snapshot->load();

    // Expect no SQL to be executed
    DB::shouldReceive('unprepared')
        ->never();
});

it('drops all current tables when requested', function () {
    // Mock SchemaBuilder
    $schemaBuilderMock = m::mock();
    $schemaBuilderMock->shouldReceive('dropAllTables')->once();

    // Mock DB facade
    DB::shouldReceive('connection')
        ->andReturnSelf(); // Returns the DB connection
    DB::shouldReceive('getSchemaBuilder')
        ->andReturn($schemaBuilderMock); // Returns the mocked schema builder
    DB::shouldReceive('getDefaultConnection')
        ->andReturn('testing'); // Returns a mock default connection
    DB::shouldReceive('reconnect')->once();

    // Instance of Snapshot
    $snapshot = new Snapshot(m::mock(FilesystemAdapter::class), 'snapshot.sql');

    // Access protected method via Reflection
    $reflection = new ReflectionMethod(Snapshot::class, 'dropAllCurrentTables');
    $reflection->setAccessible(true);

    // Invoke the protected method
    $reflection->invoke($snapshot);
});
