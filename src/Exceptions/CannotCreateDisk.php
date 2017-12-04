<?php

namespace Spatie\DbSnapshots\Exceptions;

use Exception;

class CannotCreateDisk extends Exception
{
    public static function diskNotDefined(string $diskName): self
    {
        $existingDiskNames = implode(array_keys(config('filesystems.disks')), ', ');

        return new static("Cannot create a disk `{$diskName}`. Known disknames are {$existingDiskNames}.");
    }
}
