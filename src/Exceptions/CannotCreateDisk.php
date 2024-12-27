<?php

namespace Spatie\DbSnapshots\Exceptions;

use Exception;

class CannotCreateDisk extends Exception
{
    public static function diskNotDefined(string $diskName): static
    {
        $disks = config('filesystems.disks', null);

        if (! $disks) {
            return new static("Cannot create a disk `{$diskName}`. There are no disks set up.");
        }

        $existingDiskNames = implode(', ', array_keys($disks));

        return new static("Cannot create a disk `{$diskName}`. Known disk names are {$existingDiskNames}.");
    }
}
