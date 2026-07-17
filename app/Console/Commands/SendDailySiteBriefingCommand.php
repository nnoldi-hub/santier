<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ProjectDailyBriefingSetting;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use App\Mail\DailySiteBriefingMail;
use App\Support\DailyBriefingBuilder;
use App\Support\DocumentBranding;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailySiteBriefingCommand extends Command
{
    protected $signature = 'briefing:send-daily';

    protected $description = 'Send the daily site briefing (email + in-app) for projects with it enabled and due today';

    public function handle(): int
    {
        // The app runs on UTC internally (config('app.timezone')) but "send_time"
        // is set and understood by users as Romania local time - compare against
        // that, not the server's UTC clock, or the briefing fires hours late/early.
        $localNow = now('Europe/Bucharest');
        $today = $localNow->toDateString();
        $nowTime = $localNow->format('H:i');
        $sent = 0;

        ProjectDailyBriefingSetting::query()
            ->where('enabled', true)
            ->where(fn ($query) => $query->whereNull('last_sent_date')->orWhereDate('last_sent_date', '<', $today))
            ->get()
            ->each(function (ProjectDailyBriefingSetting $setting) use ($today, $nowTime, &$sent) {
                $sendTime = $setting->send_time?->format('H:i');
                if ($sendTime === null || $sendTime > $nowTime) {
                    return;
                }

                $project = Project::find($setting->project_id);
                if (!$project) {
                    return;
                }

                $briefing = DailyBriefingBuilder::build($project);
                $recipients = User::whereIn('id', $setting->recipient_user_ids ?? [])->get();
                $channels = array_merge(ProjectDailyBriefingSetting::$defaultChannels, $setting->channels ?? []);
                $whiteLabel = (bool) (DocumentBranding::resolve((int) $setting->tenant_id)['white_label'] ?? false);

                foreach ($recipients as $recipient) {
                    if (!empty($channels['in_app'])) {
                        $recipient->notify(new OperationalReminderNotification(
                            event: 'daily_briefing',
                            title: 'Memento zilnic',
                            message: sprintf('Memento pentru %s: %d blocaj(e) azi.', $project->name, count($briefing['blockers'])),
                            entityType: 'project',
                            entityId: (int) $project->id,
                            projectId: $project->id,
                            projectName: $project->name,
                            url: route('daily-briefing.show', $project->id),
                            severity: count($briefing['blockers']) > 0 ? 'high' : 'low',
                        ));
                    }

                    if (!empty($channels['email']) && $recipient->email) {
                        try {
                            Mail::to($recipient->email)->send(new DailySiteBriefingMail($project, $briefing, $recipient->name, $whiteLabel));
                        } catch (\Throwable $e) {
                            $this->error("Failed to send daily briefing mail for project {$project->id} to {$recipient->email}: {$e->getMessage()}");
                        }
                    }
                }

                $setting->update(['last_sent_date' => $today]);
                $sent++;
            });

        $this->info($sent > 0 ? "Sent {$sent} daily briefing(s)." : 'No daily briefings were due.');

        return self::SUCCESS;
    }
}
