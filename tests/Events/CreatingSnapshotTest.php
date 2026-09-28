<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\DbSnapshots\Events\CreatingSnapshot;

test('creating a snapshot fires the creating snapshot event', function () {
    Event::fake();

    Artisan::call('snapshot:create', ['name' => 'my-snapshot']);

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
        return $event->fileName === 'my-snapshot.sql';
    });
});

test('creating a snapshot with exclude will pass excluded tables', function () {
    Event::fake();

    Artisan::call('snapshot:create', ['name' => 'my-snapshot', '--exclude' => ['tb1', 'tb2']]);

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
        return ($event->fileName === 'my-snapshot.sql') && $event->exclude === ['tb1', 'tb2'];
    });
});

it('normalizes the tables option', function (array|string $tables, array $expectedTables) {
    Event::fake();

    Artisan::call('snapshot:create', ['--table' => $tables]);

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) use ($expectedTables) {
        return $event->tables === $expectedTables && $event->exclude === null;
    });
})->with([
    'array' => [['users', 'posts'], ['users', 'posts']],
    'string' => ['users,posts', ['users', 'posts']],
    'comma separated value in array' => [['users,posts'], ['users', 'posts']],
    'mixed values' => [['users,posts', 'models'], ['users', 'posts', 'models']],
    'values with whitespace' => [['users, posts '], ['users', 'posts']],
    'empty values' => [['users,,posts,'], ['users', 'posts']],
]);

it('normalizes the exclude option', function (array|string $exclude, array $expectedExclude) {
    Event::fake();

    Artisan::call('snapshot:create', ['--exclude' => $exclude]);

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) use ($expectedExclude) {
        return $event->exclude === $expectedExclude && $event->tables === null;
    });
})->with([
    'array' => [['users', 'posts'], ['users', 'posts']],
    'string' => ['users,posts', ['users', 'posts']],
    'comma separated value in array' => [['users,posts'], ['users', 'posts']],
    'values with whitespace' => [['users, posts '], ['users', 'posts']],
]);

it('normalizes tables and exclude from the config', function (array|string $configValue, array $expectedValue) {
    Event::fake();

    config()->set('db-snapshots.tables', $configValue);
    Artisan::call('snapshot:create');

    config()->set('db-snapshots.tables', null);
    config()->set('db-snapshots.exclude', $configValue);
    Artisan::call('snapshot:create', ['name' => 'excluded']);

    Event::assertDispatched(CreatingSnapshot::class, fn (CreatingSnapshot $event) => $event->tables === $expectedValue);
    Event::assertDispatched(CreatingSnapshot::class, fn (CreatingSnapshot $event) => $event->exclude === $expectedValue);
})->with([
    'array' => [['users', 'posts'], ['users', 'posts']],
    'string' => ['users,posts', ['users', 'posts']],
    'string with whitespace' => ['users, posts', ['users', 'posts']],
]);

it('prefers the command options over the config', function () {
    Event::fake();

    config()->set('db-snapshots.tables', ['models']);

    Artisan::call('snapshot:create', ['--table' => ['users,posts']]);

    Event::assertDispatched(CreatingSnapshot::class, fn (CreatingSnapshot $event) => $event->tables === ['users', 'posts']);
});

it('ignores exclude when tables are given', function () {
    Event::fake();

    Artisan::call('snapshot:create', ['--table' => ['users'], '--exclude' => ['posts']]);

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
        return $event->tables === ['users'] && $event->exclude === null;
    });
});

it('passes tables and exclude as null when nothing is configured', function () {
    Event::fake();

    Artisan::call('snapshot:create');

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
        return $event->tables === null && $event->exclude === null && $event->extraOptions === [];
    });
});

it('does not split extra options passed via the command line on commas', function () {
    Event::fake();

    Artisan::call('snapshot:create', ['--extraOptions' => ['--where=id IN (1,2)', '--skip-comments']]);

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
        return $event->extraOptions === ['--where=id IN (1,2)', '--skip-comments'];
    });
});

it('splits extra options from the config when given as a string', function () {
    Event::fake();

    config()->set('db-snapshots.extraOptions', '--skip-comments,--no-tablespaces');

    Artisan::call('snapshot:create');

    Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
        return $event->extraOptions === ['--skip-comments', '--no-tablespaces'];
    });
});
