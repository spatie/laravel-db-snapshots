<?php

namespace Spatie\DbSnapshots\Exceptions;

use Exception;

class CannotLoadSnapshot extends Exception
{
    public static function unterminatedStringLiteral(string $fileName): static
    {
        return new static("Cannot stream snapshot `{$fileName}`: it ends inside an unterminated string literal, so the statements after the last complete one could not be split and were not loaded.");
    }
}
