<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Spatie\DbSnapshots\Test\TestCase;
use Illuminate\Support\Facades\Artisan;

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
}
