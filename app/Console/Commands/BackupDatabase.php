<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * php artisan linkora:backup
 *
 * Dumps the MySQL database to storage/app/backups/linkora-YYYYmmdd-His.sql.gz
 * and keeps the newest N backups (BACKUP_KEEP, default 14). Scheduled daily.
 * Copy these files off the server too (e.g. to cloud storage) — a backup on the
 * same disk does not protect against losing the server.
 */
class BackupDatabase extends Command
{
    protected $signature = 'linkora:backup {--keep= : How many backups to keep}';

    protected $description = 'Back up the database to storage/app/backups';

    public function handle()
    {
        $db = config('database.connections.' . config('database.default'));
        if (($db['driver'] ?? null) !== 'mysql') {
            $this->error('Only MySQL backups are supported.');

            return 1;
        }

        Storage::makeDirectory('backups');
        $file = 'backups/linkora-' . now()->format('Ymd-His') . '.sql.gz';
        $path = Storage::path($file);

        $process = new Process([
            config('linkora.backup.mysqldump'),
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            $db['database'],
        ], null, ['MYSQL_PWD' => (string) $db['password']]); // password via env, never on the command line
        $process->setTimeout(600);

        $gz = gzopen($path, 'wb9');
        $process->run(function ($type, $buffer) use ($gz) {
            if ($type === Process::OUT) {
                gzwrite($gz, $buffer);
            }
        });
        gzclose($gz);

        if (! $process->isSuccessful()) {
            @unlink($path);
            $this->error('Backup failed: ' . trim($process->getErrorOutput()));

            return 1;
        }

        $keep = max(1, (int) ($this->option('keep') ?: config('linkora.backup.keep', 14)));
        $old = collect(Storage::files('backups'))->filter(fn ($f) => \Illuminate\Support\Str::endsWith($f, '.sql.gz'))->sort()->values();
        $old->slice(0, max(0, $old->count() - $keep))->each(fn ($f) => Storage::delete($f));

        $this->info('Backup saved: ' . $file . ' (' . round(filesize($path) / 1024, 1) . ' KB)');

        return 0;
    }
}
