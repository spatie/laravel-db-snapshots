<?php

// inspirated by:https://dzone.com/articles/processing-large-files-using-php

namespace Spatie\DbSnapshots;

use Exception;
use SplFileObject;

class Bigfile
{
    protected $file;

    public function __construct($filename, $mode = 'r', $compressed = false)
    {
        if (! file_exists($filename)) {
            throw new Exception('File not found');
        }
        $this->file = new SplFileObject(($compressed ? 'compress.zlib://'.$filename : $filename), $mode);
    }

    public function iterateText()
    {
        $count = 0;

        while (! $this->file->eof()) {
            yield $this->file->fgets();
            $count++;
        }

        return $count;
    }
}
