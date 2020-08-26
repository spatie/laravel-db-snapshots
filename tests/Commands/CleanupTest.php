<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Illuminate\Support\Facades\Artisan;
use Spatie\DbSnapshots\Test\TestCase;

class CleanupTest extends TestCase
{
    /** @test */
    public function it_can_delete_old_snapshots_keeping_the_desired_number_of_snapshots()
    {
        // Add sleep to make sure files do not have the same modified time.
        // They may not sort properly if all have the same timestamp.
        $this->clearDisk();

        $this->disk->put('snapshot1.sql', 'new content');

        sleep(1);

        $this->disk->put('snapshot2.sql', 'new content');

        Artisan::call('snapshot:cleanup', ['--keep' => 1]);

        $this->disk->assertMissing('snapshot1.sql');
        $this->disk->assertExists('snapshot2.sql');
    }

    /** @test */
    public function it_can_delete_all_snapshots_if_keep_is_zero()
    {
        $this->clearDisk();

        $this->disk->put('snapshot.sql', 'new content');

        Artisan::call('snapshot:cleanup --keep=0');

        $this->disk->assertMissing('snapshot.sql');
    }

    /** @test */
    public function it_warns_if_keep_is_not_specified()
    {
        $this->clearDisk();

        $this->disk->put('snapshot.sql', 'new content');

        Artisan::call('snapshot:cleanup');

        $this->disk->assertExists('snapshot.sql');
        $this->seeInConsoleOutput('No value for option --keep.');
    }
}
