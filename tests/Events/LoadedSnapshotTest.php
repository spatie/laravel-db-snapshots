<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\DbSnapshots\Events\LoadedSnapshot;

test('after a snapshot has been loaded the loaded snapshot event will be fired', function () {
    Event::fake();

    Artisan::call('snapshot:load', ['name' => 'snapshot2']);

    Event::assertDispatched(LoadedSnapshot::class, function (LoadedSnapshot $event) {
        return $event->snapshot->fileName === 'snapshot2.sql';
    });
});
