<?php

namespace Spatie\DbSnapshots\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\DbSnapshots\Helpers\Format;
use Spatie\DbSnapshots\SnapshotFactory;

class Create extends Command
{
    protected $signature = 'snapshot:create {name?} {--connection=} {--compress} {--table=*} {--exclude=*} {--extraOptions=*}';

    protected $description = 'Create a new snapshot.';

    public function handle()
    {
        $this->info('Creating new snapshot...');

        $connectionName = $this->option('connection')
            ?: config('db-snapshots.default_connection')
            ?? config('database.default');

        $snapshotName = $this->getSnapshotName();

        $compress = $this->option('compress') || config('db-snapshots.compress', false);

        $tables = $this->normalizeTables(
            $this->option('table'),
            config('db-snapshots.tables', null)
        );

        if (is_null($tables)) {
            $exclude = $this->normalizeTables(
                $this->option('exclude'),
                config('db-snapshots.exclude', null)
            );
        } else {
            $exclude = null;
        }

        $extraOptions = $this->option('extraOptions') ?: config('db-snapshots.extraOptions', []);
        $extraOptions = is_string($extraOptions) ? explode(',', $extraOptions) : $extraOptions;

        $snapshot = app(SnapshotFactory::class)->create(
            $snapshotName,
            config('db-snapshots.disk'),
            $connectionName,
            $compress,
            $tables,
            $exclude,
            $extraOptions
        );

        $size = Format::humanReadableSize($snapshot->size());

        $this->info("Snapshot `{$snapshotName}` created (size: {$size})");
    }

    private function normalizeTables(array|string|null $optionValue, array|string|null $configValue): ?array
    {
        $value = $optionValue ?: $configValue;

        if (is_null($value)) {
            return null;
        }

        return collect((array) $value)
            ->flatMap(fn (string $item): array => explode(',', $item))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function getSnapshotName(): string
    {
        if (! is_null($this->option('connection')) && is_null($this->argument('name'))) {
            return $this->option('connection'). "_". Carbon::now()->format('Y-m-d_H-i-s');
        }

        return $this->argument('name') ?? Carbon::now()->format('Y-m-d_H-i-s');
    }
}
