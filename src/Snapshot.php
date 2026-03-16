<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;

class Snapshot
{
    public Disk $disk;

    public string $fileName;

    public string $name;

    public ?string $compressionExtension = null;

    private bool $useStream = false;

    public const STREAM_BUFFER_SIZE = 16384;

    public function __construct(Disk $disk, string $fileName)
    {
        $this->disk = $disk;

        $this->fileName = $fileName;

        $pathinfo = pathinfo($fileName);

        if ($pathinfo['extension'] === 'gz') {
            $this->compressionExtension = $pathinfo['extension'];
            $fileName = $pathinfo['filename'];
        }

        $this->name = pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function useStream(): self
    {
        $this->useStream = true;

        return $this;
    }

    public function load(?string $connectionName = null, bool $dropTables = true): void
    {
        event(new LoadingSnapshot($this));

        if ($connectionName !== null) {
            DB::setDefaultConnection($connectionName);
        }

        if ($dropTables) {
            $this->dropAllCurrentTables();
        }

        $this->useStream ? $this->loadStream($connectionName) : $this->loadAsync($connectionName);

        event(new LoadedSnapshot($this));
    }

    protected function loadAsync(?string $connectionName = null): void
    {
        $dbDumpContents = $this->disk->get($this->fileName);

        if ($this->compressionExtension === 'gz') {
            $dbDumpContents = gzdecode($dbDumpContents);
        }

        $dbDumpContents = $this->filterPsqlMetaCommands($dbDumpContents);

        DB::connection($connectionName)->unprepared($dbDumpContents);
    }

    protected function filterPsqlMetaCommands(string $contents): string
    {
        $lines = explode("\n", $contents);
        $filteredLines = array_filter($lines, fn (string $line) => ! $this->isPsqlMetaCommand(trim($line)));

        return implode("\n", $filteredLines);
    }

    protected function isASqlComment(string $line): bool
    {
        return str_starts_with($line, '--');
    }

    protected function shouldIgnoreLine(string $line): bool
    {
        $line = trim($line);

        return empty($line) || $this->isASqlComment($line) || $this->isPsqlMetaCommand($line);
    }

    protected function isPsqlMetaCommand(string $line): bool
    {
        return str_starts_with($line, '\\');
    }

    protected function loadStream(?string $connectionName = null): void
    {
        LazyCollection::make(function () {
            $isCompressedStream = $this->compressionExtension === 'gz';

            $stream = $isCompressedStream
                ? gzopen($this->disk->path($this->fileName), 'r')
                : $this->disk->readStream($this->fileName);

            $statement = '';
            $line = '';
            $inString = false;
            $stringIsEscaped = false;
            while (! ($isCompressedStream ? gzeof($stream) : feof($stream))) {
                $chunk = $isCompressedStream
                        ? gzread($stream, self::STREAM_BUFFER_SIZE)
                        : fread($stream, self::STREAM_BUFFER_SIZE);

                foreach (str_split($chunk) as $char) {
                    $line .= $char;

                    if ($inString) {
                        if ($stringIsEscaped) {
                            $stringIsEscaped = false;

                            continue;
                        }

                        if ($char === '\\') {
                            $stringIsEscaped = true;

                            continue;
                        }

                        if ($char === "'") {
                            $inString = false;
                        }

                        continue;
                    }

                    if ($char === "'") {
                        $inString = true;

                        continue;
                    }

                    if ($char !== "\n") {
                        continue;
                    }

                    $this->appendStreamLine($statement, $line);
                    $line = '';

                    if (str_ends_with(trim($statement), ';')) {
                        yield $statement;
                        $statement = '';
                    }
                }
            }

            if ($line !== '') {
                $this->appendStreamLine($statement, $line);
            }

            if (str_ends_with(trim($statement), ';')) {
                yield $statement;
            }
        })->each(function (string $statement) use ($connectionName) {
            DB::connection($connectionName)->unprepared($statement);
        });
    }

    protected function appendStreamLine(string &$statement, string $line): void
    {
        if ($this->shouldIgnoreLine($line)) {
            return;
        }

        $statement .= $line;
    }

    public function delete(): void
    {
        event(new DeletingSnapshot($this));

        $this->disk->delete($this->fileName);

        event(new DeletedSnapshot($this->fileName, $this->disk));
    }

    public function size(): int
    {
        return $this->disk->size($this->fileName);
    }

    public function createdAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->disk->lastModified($this->fileName));
    }

    protected function dropAllCurrentTables(): void
    {
        DB::connection(DB::getDefaultConnection())
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect();
    }
}
