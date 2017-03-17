<?php

namespace Spatie\DbSnapshots\Test;

use Spatie\DbSnapshots\SnapshotRepository;

class SnapshotRepositoryTest extends TestCase
{
    /** @test */
    public function it_can_load_snapshots_from_a_disk()
    {
        $snapshots = app(SnapshotRepository::class)->getAll();

        dd($snapshots);
    }
}