<?php

namespace Spatie\DbSnapshots;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;

class SnapshotRepository
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    protected $disk;

    public function __construct(Disk $disk)
    {
        $this->disk = $disk;
    }

    public function getAll(): Collection
    {
        return collect($this->disk->allFiles())
            ->filter(function (string $fileName) {
                $pathinfo = pathinfo($fileName);

                if ($pathinfo['extension'] === 'gz') {
                    $fileName = $pathinfo['filename'];
                }

                return pathinfo($fileName, PATHINFO_EXTENSION) === 'sql';
            })
            ->map(function (string $fileName) {
                return new Snapshot($this->disk, $fileName);
            })
            ->sortByDesc(function (Snapshot $snapshot) {
                return $snapshot->createdAt()->toDateTimeString();
            });
    }

    public function findByName(string $name)
    {
        return $this->getAll()->first(function (Snapshot $snapshot) use ($name) {
            return $snapshot->name === $name;
        });
    }
}
