<?php

namespace Spatie\DbSnapshots\Test;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Filesystem\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\DbSnapshots\DbSnapshotsServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    protected $disk;

    public function setUp(): void
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
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => __DIR__.'/temp/database.sqlite',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.snapshots', [
            'driver' => 'local',
            'root' => __DIR__.'/temp/snapshotsDisk',
        ]);
    }

    protected function assertFileOnDiskPassesRegex($fileName, $needle)
    {
        $this->disk->assertExists($fileName);

        $contents = $this->disk->get($fileName);

        $this->assertRegExp($needle, $contents);
    }

    protected function setupDatabase()
    {
        $databasePath = __DIR__.'/temp/database.sqlite';

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
        foreach (range(1, 3) as $i) {
            $this->disk->put("snapshot{$i}.sql", $this->getSnapshotContent("snapshot{$i}"));
        }

        $this->disk->put('snapshot4.sql.gz', gzencode($this->getSnapshotContent('snapshot4')));

        $this->disk->put('otherfile.txt', 'not a snapshot');
    }

    protected function getSnapshotContent($modelName): string
    {
        $snapshotContent = file_get_contents(__DIR__.'/fixtures/snapshotContent.sql');

        return str_replace('%%modelName%%', $modelName, $snapshotContent);
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
            $this->assertStringContainsString((string) $searchString, $output);
        }
    }
}
