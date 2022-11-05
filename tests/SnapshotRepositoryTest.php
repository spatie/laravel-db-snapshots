<?php

use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

beforeEach(function () {
    $this->repository = app(SnapshotRepository::class);
});

it('can load snapshots from a disk', function () {
    $snapshots = $this->repository->getAll();

    expect($snapshots)->toHaveCount(4)
        ->and($snapshots->first())->toBeInstanceOf(Snapshot::class);
});

it('can get a snapshot by name')
    ->expect(fn () => $this->repository->findByName('snapshot2'))
    ->toBeInstanceOf(Snapshot::class)
    ->and(fn () => $this->repository->findByName('snapshot5'))
    ->toBeNull();

it('can find gz compressed snapshots', function () {
    $snapshot = $this->repository->findByName('snapshot4');

    expect($snapshot)->toBeInstanceOf(Snapshot::class)
        ->and($snapshot->compressionExtension)->toEqual('gz')
        ->and($this->repository->findByName('snapshot5'))->toBeNull();
});
