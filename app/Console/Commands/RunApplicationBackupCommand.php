<?php

namespace App\Console\Commands;

use Ifsnop\Mysqldump\Mysqldump;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class RunApplicationBackupCommand extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Create a full backup (database dump + uploaded files + .env) using pure-PHP tooling, no shell subprocesses';

    private const KEEP_LAST = 7;

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($backupDir);

        $timestamp = now()->format('Y-m-d_His');
        $sqlPath = $backupDir . "/database-{$timestamp}.sql";
        $zipPath = $backupDir . "/modulia-backup-{$timestamp}.zip";

        $this->dumpDatabase($sqlPath);
        $this->buildArchive($zipPath, $sqlPath);

        File::delete($sqlPath);
        $this->pruneOldBackups($backupDir);

        $this->info("Backup creat: {$zipPath}");

        return self::SUCCESS;
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";

        (new Mysqldump($dsn, $config['username'], $config['password']))->start($sqlPath);
    }

    private function buildArchive(string $zipPath, string $sqlPath): void
    {
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFile($sqlPath, 'database.sql');

        $publicStoragePath = storage_path('app/public');
        if (File::isDirectory($publicStoragePath)) {
            $this->addDirectoryToZip($zip, $publicStoragePath, 'uploads');
        }

        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $zip->addFile($envPath, '.env');
        }

        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipFolder): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $relativePath = $zipFolder . '/' . substr($file->getPathname(), strlen($sourcePath) + 1);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relativePath));
        }
    }

    private function pruneOldBackups(string $backupDir): void
    {
        collect(File::files($backupDir))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->slice(self::KEEP_LAST)
            ->each(fn ($file) => File::delete($file->getPathname()));
    }
}
