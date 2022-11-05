<?php

use Illuminate\Support\Facades\Artisan;

it('can delete old snapshots keeping the desired number of snapshots', function () {
    // Add sleep to make sure files do not have the same modified time.
    // They may not sort properly if all have the same timestamp.
    $this->clearDisk();

    $this->disk->put('snapshot1.sql', 'new content');

    sleep(1);

    $this->disk->put('snapshot2.sql', 'new content');

    Artisan::call('snapshot:cleanup', ['--keep' => 1]);

    $this->disk->assertMissing('snapshot1.sql');
    $this->disk->assertExists('snapshot2.sql');
});

it('can delete all snapshots if keep is zero', function () {
    $this->clearDisk();

    $this->disk->put('snapshot.sql', 'new content');

    Artisan::call('snapshot:cleanup --keep=0');

    $this->disk->assertMissing('snapshot.sql');
});

it('warns if keep is not specified', function () {
    $this->clearDisk();

    $this->disk->put('snapshot.sql', 'new content');

    Artisan::call('snapshot:cleanup');

    $this->disk->assertExists('snapshot.sql');
    $this->seeInConsoleOutput('No value for option --keep.');
});
