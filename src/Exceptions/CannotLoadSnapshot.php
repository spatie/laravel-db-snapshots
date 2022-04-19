<?php

namespace Spatie\DbSnapshots\Exceptions;

use Exception;

class CannotLoadSnapshot extends Exception
{
    public static function fileNotReadable(string $fileName): static
    {
        return new static("Cannot read local snapshot file `{$fileName}`.");
    }
}
