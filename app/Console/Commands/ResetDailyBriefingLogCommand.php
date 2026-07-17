<?php

namespace App\Console\Commands;

use App\Models\ProjectDailyBriefingSetting;
use Illuminate\Console\Command;

class ResetDailyBriefingLogCommand extends Command
{
    protected $signature = 'briefing:reset-log {project : ID-ul proiectului}';

    protected $description = 'Sterge garda "last_sent_date" a unui proiect, ca briefing:send-daily sa poata retrimite azi (util pentru testare)';

    public function handle(): int
    {
        $projectId = (int) $this->argument('project');
        $setting = ProjectDailyBriefingSetting::where('project_id', $projectId)->first();

        if (!$setting) {
            $this->error("Nu exista setare de memento zilnic pentru proiectul #{$projectId}.");

            return self::FAILURE;
        }

        $setting->update(['last_sent_date' => null]);

        $this->info("Garda anti-dublare a fost resetata pentru proiectul #{$projectId} - urmatoarea rulare briefing:send-daily va putea trimite din nou azi.");

        return self::SUCCESS;
    }
}
