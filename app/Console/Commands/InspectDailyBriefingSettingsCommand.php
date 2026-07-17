<?php

namespace App\Console\Commands;

use App\Models\ProjectDailyBriefingSetting;
use Illuminate\Console\Command;

class InspectDailyBriefingSettingsCommand extends Command
{
    protected $signature = 'briefing:inspect';

    protected $description = 'Afiseaza ora/fusul orar curent al serverului si setarile mementoului zilnic salvate, pentru diagnosticare';

    public function handle(): int
    {
        $this->info('Ora curenta server: ' . now()->format('Y-m-d H:i:s') . ' (' . config('app.timezone') . ')');
        $this->newLine();

        $settings = ProjectDailyBriefingSetting::query()->with('project:id,name')->get();

        if ($settings->isEmpty()) {
            $this->warn('Nicio setare de memento zilnic in baza de date.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Proiect', 'Activat', 'Ora trimiterii', 'Ultima trimitere', 'Destinatari'],
            $settings->map(fn (ProjectDailyBriefingSetting $s) => [
                $s->id,
                $s->project?->name ?? "proiect #{$s->project_id}",
                $s->enabled ? 'da' : 'nu',
                $s->send_time?->format('H:i') ?? '-',
                optional($s->last_sent_date)->toDateString() ?? '-',
                implode(',', $s->recipient_user_ids ?? []),
            ])
        );

        return self::SUCCESS;
    }
}
