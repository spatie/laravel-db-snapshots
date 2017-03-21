<?php

namespace Spatie\DbSnapshots\Test;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Artisan;
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

        $this->setUpDisk();

        $this->setupDatabase();

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
            'database' => __DIR__ . '/fixtures/database.sqlite',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.snapshots', [
            'driver' => 'local',
            'root' => __DIR__ . '/temp/snapshotsDisk',
        ]);
    }

    protected function assertFileOnDiskContains($fileName, $needle)
    {
        $this->disk->assertExists($fileName);

        $contents = $this->disk->get($fileName);

        $this->assertContains($needle, $contents);
    }

    protected function setupDatabase()
    {
        $databasePath = __DIR__ . '/fixtures/database.sqlite';

        if (file_exists($databasePath)) {
            unlink($databasePath);
        }

        if (! file_exists($databasePath)) {
            file_put_contents($databasePath, '');
        }

        $this->app['db']->connection()->getSchemaBuilder()->create('models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });
    }

    protected function setUpDisk()
    {
        $this->disk = app(Factory::class)->disk('snapshots');

        $this->clearDisk();
        $this->createDummySnapshots();
    }

    protected function clearDisk()
    {
        $this->disk->delete($this->disk->allFiles());
    }

    protected function createDummySnapshots()
    {
        $this->disk->put('snapshot1.sql', '');
        $this->disk->put('snapshot2.sql', '');
        $this->disk->put('snapshot3.sql', '');
    }

    /**
     * @param string|array $searchStrings
     */
    protected function seeInConsoleOutput($searchStrings)
    {
        if (! is_array($searchStrings)) {
            $searchStrings = [$searchStrings];
        }

        $output = Artisan::output();

        foreach ($searchStrings as $searchString) {
            $this->assertContains((string) $searchString, $output);
        }
    }
}
