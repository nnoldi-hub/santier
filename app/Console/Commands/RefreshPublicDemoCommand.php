<?php

namespace App\Console\Commands;

use Database\Seeders\PublicDemoSeeder;
use Illuminate\Console\Command;

class RefreshPublicDemoCommand extends Command
{
    protected $signature = 'demo:refresh';

    protected $description = 'Refresh public demo account and demo dataset';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => PublicDemoSeeder::class,
            '--force' => true,
        ]);

        $this->info('Public demo account and dataset refreshed.');

        return self::SUCCESS;
    }
}
