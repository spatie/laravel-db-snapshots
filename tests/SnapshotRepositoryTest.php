<?php

namespace Spatie\DbSnapshots\Test;

use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

class SnapshotRepositoryTest extends TestCase
{
    /** @var \Spatie\DbSnapshots\SnapshotRepository */
    protected $repository;

    public function setUp()
    {
        parent::setUp();

        $this->repository = app(SnapshotRepository::class);
    }

    /** @test */
    public function it_can_load_snapshots_from_a_disk()
    {
        $snapshots = $this->repository->getAll();

        $this->assertCount(4, $snapshots);

        $this->assertInstanceOf(Snapshot::class, $snapshots->first());
    }

    /** @test */
    public function it_can_get_a_snapshot_by_name()
    {
        $this->assertInstanceOf(Snapshot::class, $this->repository->findByName('snapshot2'));

        $this->assertNull($this->repository->findByName('snapshot5'));
    }

    /** @test */
    public function it_can_find_gz_compressed_snapshots()
    {
        $snapshot = $this->repository->findByName('snapshot4');

        $this->assertInstanceOf(Snapshot::class, $snapshot);

        $this->assertEquals('gz', $snapshot->compressionExt);

        $this->assertNull($this->repository->findByName('snapshot5'));
    }
}
