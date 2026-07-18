<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarkMigrationRanCommand extends Command
{
    protected $signature = 'migrations:mark-ran {migration : Numele fisierului de migratie, fara extensia .php}';

    protected $description = 'Insereaza manual o intrare in tabela migrations, pentru cazul cand tabela tinta exista deja dar migratia nu e inregistrata ca rulata';

    public function handle(): int
    {
        $migration = $this->argument('migration');

        if (DB::table('migrations')->where('migration', $migration)->exists()) {
            $this->warn("Migratia \"{$migration}\" este deja inregistrata ca rulata.");

            return self::SUCCESS;
        }

        $nextBatch = (int) DB::table('migrations')->max('batch') + 1;

        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => $nextBatch,
        ]);

        $this->info("Migratia \"{$migration}\" a fost marcata ca rulata (batch {$nextBatch}).");

        return self::SUCCESS;
    }
}
