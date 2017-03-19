<?php

namespace Spatie\DbSnapshots\Test;

use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

class SnapshotRepositoryTest extends TestCase
{
    /** @test */
    public function it_can_load_snapshots_from_a_disk()
    {
        $this->disk->put('file1.txt', '');
        $this->disk->put('file2.txt', '');
        $this->disk->put('file3.txt', '');

        $snapshots = app(SnapshotRepository::class)->getAll();

        $this->assertCount(3, $snapshots);

        $this->assertInstanceOf(Snapshot::class, $snapshots->first());
    }
}