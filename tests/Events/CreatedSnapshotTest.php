<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\DbSnapshots\Events\CreatedSnapshot;

test('after the snapshot has been created the created snapshot event will be fired', function () {
    Event::fake();

    Artisan::call('snapshot:create', ['name' => 'my-snapshot']);

    Event::assertDispatched(CreatedSnapshot::class, function (CreatedSnapshot $event) {
        return $event->snapshot->fileName === 'my-snapshot.sql';
    });
});
