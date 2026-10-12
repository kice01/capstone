<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'database:backup
        {--path= : Destination folder for the SQL backup}
        {--keep=7 : Number of days of backups to retain}
        {--dry-run : Show the backup destination without running mysqldump}';

    protected $description = 'Create and rotate a private SQL backup of the MySQL database';

    public function handle(Filesystem $files): int
    {
        $connection = config('database.default');
        if (! in_array($connection, ['mysql', 'mariadb'], true)) {
            $this->error('Database backup currently supports MySQL and MariaDB connections.');
            return self::FAILURE;
        }

        $database = (string) config("database.connections.{$connection}.database");
        if ($database === '') {
            $this->error('No database name is configured.');
            return self::FAILURE;
        }

        $directory = $this->option('path')
            ? (string) $this->option('path')
            : storage_path('app/private/backups');
        $keepDays = max(1, (int) $this->option('keep'));
        $backupPath = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR
            .$database.'_'.now()->format('Ymd_His').'.sql';

        if ($this->option('dry-run')) {
            $this->info("Would write database backup to: {$backupPath}");
            return self::SUCCESS;
        }

        $files->ensureDirectoryExists($directory, 0700, true);
        $temporaryOptionsFile = tempnam(sys_get_temp_dir(), 'extrack-mysql-');
        if ($temporaryOptionsFile === false) {
            $this->error('Could not create a temporary MySQL options file.');
            return self::FAILURE;
        }

        try {
            @chmod($temporaryOptionsFile, 0600);
            if (file_put_contents($temporaryOptionsFile, $this->mysqlClientOptions($connection)) === false) {
                $this->error('Could not write the temporary MySQL options file.');
                return self::FAILURE;
            }
            $binary = (string) config('database.dump_binary', 'mysqldump');
            if (PHP_OS_FAMILY === 'Windows' && $binary === 'mysqldump'
                && is_file('C:\\xampp\\mysql\\bin\\mysqldump.exe')) {
                $binary = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
            }

            $process = new Process([
                $binary,
                "--defaults-extra-file={$temporaryOptionsFile}",
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                '--routines',
                '--triggers',
                '--databases',
                $database,
                "--result-file={$backupPath}",
            ]);
            $process->setTimeout(null);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($backupPath) || filesize($backupPath) === 0) {
                @unlink($backupPath);
                $this->error('Database backup failed. '.$process->getErrorOutput());
                return self::FAILURE;
            }

            $this->rotateOldBackups($files, $directory, $database, $keepDays);
            $this->info("Database backup created: {$backupPath}");
            return self::SUCCESS;
        } finally {
            @unlink($temporaryOptionsFile);
        }
    }

    private function mysqlClientOptions(string $connection): string
    {
        $config = config("database.connections.{$connection}");
        $quote = fn ($value) => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value).'"';

        return "[client]\n"
            .'host='.$quote($config['host'] ?? '127.0.0.1')."\n"
            .'port='.$quote($config['port'] ?? 3306)."\n"
            .'user='.$quote($config['username'] ?? '')."\n"
            .'password='.$quote($config['password'] ?? '')."\n"
            .(!empty($config['unix_socket']) ? 'socket='.$quote($config['unix_socket'])."\n" : '');
    }

    private function rotateOldBackups(Filesystem $files, string $directory, string $database, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->getTimestamp();
        foreach ($files->files($directory) as $file) {
            if (str_starts_with($file->getFilename(), $database.'_')
                && $file->getExtension() === 'sql'
                && $file->getMTime() < $cutoff) {
                $files->delete($file->getPathname());
            }
        }
    }
}
