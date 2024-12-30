<?php

namespace Spatie\DbSnapshots;

use Illuminate\Contracts\Filesystem\Filesystem as Disk;
use Illuminate\Support\Collection;

class SnapshotRepository
{
    public function __construct(
        protected Disk $disk,
    ) {
        //
    }

    /**
     * @return Collection<Snapshot>
     */
    public function getAll(): Collection
    {
        return collect($this->disk->allFiles())
            ->filter(function (string $fileName) {
                $pathinfo = pathinfo($fileName);

                if (($pathinfo['extension'] ?? null) === 'gz') {
                    $fileName = $pathinfo['filename'];
                }

                return pathinfo($fileName, PATHINFO_EXTENSION) === 'sql';
            })
            ->map(fn (string $fileName) => new Snapshot($this->disk, $fileName))
            ->sortByDesc(fn (Snapshot $snapshot) => $snapshot->createdAt()->toDateTimeString());
    }

    public function findByName(string $name): ?Snapshot
    {
        return $this->getAll()->first(fn (Snapshot $snapshot) => $snapshot->name === $name);
    }
}
