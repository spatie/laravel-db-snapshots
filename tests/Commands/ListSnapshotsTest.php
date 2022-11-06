<?php

use Illuminate\Support\Facades\Artisan;

it('can list all snapshots', function () {
    Artisan::call('snapshot:list');

    seeInConsoleOutput(['snapshot1', 'snapshot2', 'snapshot3']);
});
