<?php

namespace Spatie\DbSnapshots\Test;


use Spatie\DbSnapshots\Helpers\Format;

class FormatTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_determine_a_human_readable_filesize()
    {
        $this->assertEquals('10 B', Format::humanReadableSize(10));
        $this->assertEquals('100 B', Format::humanReadableSize(100));
        $this->assertEquals('1000 B', Format::humanReadableSize(1000));
        $this->assertEquals('9.77 KB', Format::humanReadableSize(10000));
        $this->assertEquals('976.56 KB', Format::humanReadableSize(1000000));
        $this->assertEquals('9.54 MB', Format::humanReadableSize(10000000));
        $this->assertEquals('9.31 GB', Format::humanReadableSize(10000000000));
    }
}
