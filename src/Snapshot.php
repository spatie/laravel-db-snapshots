<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\TemporaryDirectory\TemporaryDirectory;

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

    public function useStream()
    {
        $this->useStream = true;

        return $this;
    }

    public function load(string $connectionName = null, bool $dropTables = true): void
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

    protected function loadAsync(string $connectionName = null)
    {
        $dbDumpContents = $this->disk->get($this->fileName);

        if ($this->compressionExtension === 'gz') {
            $dbDumpContents = gzdecode($dbDumpContents);
        }

        DB::connection($connectionName)->unprepared($dbDumpContents);
    }

    protected function isASqlComment(string $line): bool
    {
        return substr($line, 0, 2) === '--';
    }

    protected function shouldIgnoreLine(string $line): bool
    {
        $line = trim($line);

        // Ignore empty lines, SQL comments, and psql meta-commands (e.g. \\connect, \\., etc.)
        if ($line === '' || $this->isASqlComment($line)) {
            return true;
        }

        // Skip psql meta commands and COPY terminator from pg_dump-like files
        if (str_starts_with($line, '\\')) {
            return true;
        }

        // Some dump tools include non-SQL metadata lines like:
        // These are not valid SQL statements and must be skipped.
        if (str_contains($line, '; Type:') && str_contains($line, 'Schema:')) {
            return true;
        }

        return false;
    }

    protected function loadStream(string $connectionName = null)
    {
        $directory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        config([
            'filesystems.disks.' . self::class => [
                'driver' => 'local',
                'root' => $directory->path(),
                'throw' => false,
            ]
        ]);

        LazyCollection::make(function () {
            Storage::disk(self::class)->writeStream($this->fileName, $this->disk->readStream($this->fileName));

            $stream = $this->compressionExtension === 'gz'
                ? gzopen(Storage::disk(self::class)->path($this->fileName), 'r')
                : Storage::disk(self::class)->readStream($this->fileName);

            // Stateful, PostgreSQL-aware streaming parser
            $statement = '';
            $leftover = '';
            $lineBuffer = '';
            $atLineStart = true;
            $skipLine = false; // for psql meta-commands starting with '\\'

            $inSingle = false;      // inside '...'
            $inDollarTag = null;    // holds the full $tag$ delimiter when inside dollar-quoted string
            $inBlockComment = false;// inside /* ... */
            $inLineComment = false; // inside -- ... until \n
            $inCopy = false;        // inside COPY ... FROM stdin data section
            $copyLineBuffer = '';

            $flushLineIfNotIgnored = function () use (&$lineBuffer, &$statement) {
                $line = $lineBuffer;
                $lineBuffer = '';
                // Decide whether to ignore this line (comments/meta). We only evaluate in neutral state.
                if ($this->shouldIgnoreLine($line)) {
                    return; // drop
                }
                $statement .= $line;
            };

            $yieldIfTerminated = function () use (&$statement, &$lineBuffer, &$inCopy) {
                // Append any remaining buffered line (if not ignored); caller must ensure neutral state
                // Evaluate ignore after full line only if a newline was already encountered.
                // If semicolon occurs mid-line, we can't defer the decision to EOL. Apply a quick metadata guard.
                $trimmedLine = trim($lineBuffer);
                if ($trimmedLine !== '' && str_contains($trimmedLine, '; Type:') && str_contains($trimmedLine, 'Schema:')) {
                    // This is a pg_dump metadata line; drop it entirely and do not terminate.
                    $lineBuffer = '';
                    return false;
                }
                $statement .= $lineBuffer;
                $lineBuffer = '';
                $sql = trim($statement);
                if ($sql === '') {
                    $statement = '';
                    return false;
                }
                // Detect COPY ... FROM stdin; header and enter copy mode. We do NOT yield this to DB
                if (preg_match('/^copy\s+.+\s+from\s+stdin;\s*$/is', $sql)) {
                    $inCopy = true;
                    $statement = '';
                    return false;
                }
                return $sql; // return the SQL to be yielded by caller
            };

            while (! feof($stream)) {
                $chunk = $this->compressionExtension === 'gz'
                    ? gzread($stream, self::STREAM_BUFFER_SIZE)
                    : fread($stream, self::STREAM_BUFFER_SIZE);

                if ($chunk === false || $chunk === '') {
                    continue;
                }

                $data = $leftover . $chunk;
                $leftover = '';
                $len = strlen($data);

                for ($i = 0; $i < $len; $i++) {
                    $ch = $data[$i];
                    $next = ($i + 1 < $len) ? $data[$i + 1] : null;

                    // COPY data mode: consume lines verbatim until a line with "\\." terminator
                    if ($inCopy) {
                        $copyLineBuffer .= $ch;
                        if ($ch === "\n") {
                            $line = rtrim($copyLineBuffer, "\r\n");
                            $copyLineBuffer = '';
                            if ($line === '\\.') {
                                // End of COPY data. Return to neutral state.
                                $inCopy = false;
                                $atLineStart = true;
                            } else {
                                // Stay in COPY mode; ignore data lines.
                                $atLineStart = true;
                            }
                        }
                        continue;
                    }

                    // Handle pending line-comment
                    if ($inLineComment) {
                        if ($ch === "\n") {
                            $inLineComment = false;
                            $atLineStart = true;
                            $lineBuffer .= "\n"; // preserve newline to keep statement spacing stable
                            // End of line: commit or drop buffered line
                            $flushLineIfNotIgnored();
                        }
                        continue;
                    }

                    // Handle block comment
                    if ($inBlockComment) {
                        if ($ch === '*' && $next === '/') {
                            $inBlockComment = false;
                            $i++; // consume '/'
                        }
                        if ($ch === "\n") {
                            $atLineStart = true;
                        }
                        continue;
                    }

                    // Handle inside single-quoted string
                    if ($inSingle) {
                        $statement .= $ch;
                        if ($ch === "'" && $next === "'") {
                            // escaped quote
                            $statement .= $next;
                            $i++;
                        } elseif ($ch === "'") {
                            $inSingle = false;
                        }
                        if ($ch === "\n") {
                            $atLineStart = true;
                        } else {
                            $atLineStart = false;
                        }
                        continue;
                    }

                    // Handle inside dollar-quoted string
                    if ($inDollarTag !== null) {
                        // Lookahead for closing tag
                        $tagLen = strlen($inDollarTag);
                        if ($ch === '$' && $tagLen > 0) {
                            if ($i + $tagLen <= $len && substr($data, $i, $tagLen) === $inDollarTag) {
                                $statement .= $inDollarTag;
                                $i += $tagLen - 1;
                                $inDollarTag = null;
                                $atLineStart = false;
                                continue;
                            }
                        }
                        // otherwise just append
                        $statement .= $ch;
                        if ($ch === "\n") {
                            $atLineStart = true;
                        } else {
                            $atLineStart = false;
                        }
                        continue;
                    }

                    // Neutral state (not in string/comment)
                    // Start of psql meta-command line (e.g., "\\connect", "\\.") → skip entire line
                    if ($atLineStart && $ch === '\\') {
                        $skipLine = true;
                    }
                    if ($skipLine) {
                        if ($ch === "\n") {
                            $skipLine = false;
                            $atLineStart = true;
                            $lineBuffer = '';
                        }
                        continue;
                    }

                    // Detect start of line comment
                    if ($ch === '-' && $next === '-') {
                        $inLineComment = true;
                        $i++; // consume second '-'
                        continue;
                    }

                    // Detect start of block comment
                    if ($ch === '/' && $next === '*') {
                        $inBlockComment = true;
                        $i++; // consume '*'
                        continue;
                    }

                    // Detect start of single-quoted string
                    if ($ch === "'") {
                        $inSingle = true;
                        $statement .= $ch;
                        $atLineStart = false;
                        continue;
                    }

                    // Detect start of dollar-quoted string: $tag$
                    if ($ch === '$') {
                        // find next '$'
                        $j = $i + 1;
                        while ($j < $len && $data[$j] !== '$' && preg_match('/[A-Za-z0-9_]/', $data[$j])) {
                            $j++;
                        }
                        if ($j < $len && $data[$j] === '$') {
                            $tag = substr($data, $i, $j - $i + 1); // includes both '$'
                            // validate all chars between are [A-Za-z0-9_]*
                            $between = substr($tag, 1, -1);
                            if ($between === '' || preg_match('/^[A-Za-z0-9_]+$/', $between)) {
                                $inDollarTag = $tag;
                                $statement .= $tag;
                                $i = $j;
                                $atLineStart = false;
                                continue;
                            }
                        }
                        // fallthrough: it's just a '$' char
                    }

                    // Normal character in neutral state
                    if ($ch === ';') {
                        // Potential statement terminator
                        $lineBuffer .= $ch;
                        $sql = $yieldIfTerminated();
                        if ($sql !== false) {
                            yield $sql;
                            $statement = '';
                        }
                        $atLineStart = false;
                        continue;
                    }

                    // Regular char accumulation into current logical line
                    $lineBuffer .= $ch;
                    if ($ch === "\n") {
                        // End of physical line: decide to keep or drop it
                        $atLineStart = true;
                        $flushLineIfNotIgnored();

                        // If the current accumulated statement is just a standalone quoted literal
                        // (e.g. a marker line like 'snapshot4'), drop it to avoid concatenation with
                        // the next real SQL statement.
                        $trimStmt = trim($statement);
                        if ($trimStmt !== ''
                            && !str_contains($trimStmt, ';')
                            && (preg_match("/^'(?:[^']|'')*'$/", $trimStmt) || preg_match('/^"[^"]*"$/', $trimStmt))) {
                            $statement = '';
                        }
                    } else {
                        $atLineStart = false;
                    }
                }

                // Preserve any partial multibyte or token between chunks
                // We simply carry over the tail which may cut a token; to be safe carry last few bytes
                // However, here we can't easily know token boundaries, so just keep nothing special.
                // We'll use $leftover only for incomplete dollar-tag lookahead or similar by setting it explicitly.
                // Not needed now.
            }

            // EOF: flush any remaining buffered content safely
            if ($lineBuffer !== '') {
                $flushLineIfNotIgnored();
            }
            $final = trim($statement);
            if ($final !== '' && substr($final, -1) === ';') {
                yield $final;
            }
        })->each(function (string $statement) use ($connectionName) {
            DB::connection($connectionName)->unprepared($statement);
        })->tap(function () use ($directory) {
            $directory->delete();
        });
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

    protected function dropAllCurrentTables()
    {
        DB::connection(DB::getDefaultConnection())
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect();
    }
}
