<?php

// inspirated by:https://dzone.com/articles/processing-large-files-using-php

namespace Spatie\DbSnapshots;

class Bigfile
{
    protected $file;

    public function __construct($filename, $mode = 'r' ,$compressed = false)
    {
        if (! file_exists($filename)) {

            throw new \Exception('File not found');
        }
        if ($compressed) {
            $this->file = new \SplFileObject('compress.zlib://'.$filename, $mode);
        }else{
            $this->file = new \SplFileObject($filename, $mode);
        }
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
