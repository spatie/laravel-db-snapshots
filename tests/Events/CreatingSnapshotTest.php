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
