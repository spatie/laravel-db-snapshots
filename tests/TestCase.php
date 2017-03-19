<?php

namespace Spatie\DbSnapshots\Test;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Spatie\DbSnapshots\DbSnapshotsServiceProvider;
use Illuminate\Contracts\Filesystem\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @var FilesystemAdapter */
    protected $disk;

    public function setUp()
    {
        parent::setUp();

        $this->disk = app(Factory::class)->disk('snapshots');

        $this->clearDisk();

        Carbon::setTestNow(Carbon::create('2017', '1', '1', '0', '0', '0'));
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            DbSnapshotsServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.snapshots', [
            'driver' => 'local',
            'root' => __DIR__ . '/snapshotsDisk',
        ]);
    }

    protected function clearDisk()
    {
        $this->disk->delete($this->disk->allFiles());
    }

    protected function assertFileOnDiskContains($fileName, $needle)
    {
        $this->disk->assertExists($fileName);

        $contents = $this->disk->get($fileName);

        $this->assertContains($needle, $contents);
    }
}
