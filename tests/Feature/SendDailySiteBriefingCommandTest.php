<?php

namespace Tests\Feature;

use App\Mail\DailySiteBriefingMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectDailyBriefingLog;
use App\Models\ProjectDailyBriefingSetting;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendDailySiteBriefingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_briefing_for_due_projects_and_is_idempotent(): void
    {
        Mail::fake();
        Notification::fake();

        $owner = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);
        $recipient = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);

        $dueProject = $this->createProject($owner, 'Proiect Due');
        ProjectDailyBriefingSetting::create([
            'tenant_id' => 1,
            'project_id' => $dueProject->id,
            'enabled' => true,
            'send_time' => now('Europe/Bucharest')->subMinute()->format('H:i'),
            'recipient_user_ids' => [$recipient->id],
            'detail_level' => 'complet',
            'channels' => ['email' => true, 'in_app' => true, 'whatsapp' => false],
        ]);

        $futureProject = $this->createProject($owner, 'Proiect Viitor');
        ProjectDailyBriefingSetting::create([
            'tenant_id' => 1,
            'project_id' => $futureProject->id,
            'enabled' => true,
            'send_time' => now('Europe/Bucharest')->addHour()->format('H:i'),
            'recipient_user_ids' => [$recipient->id],
            'detail_level' => 'complet',
            'channels' => ['email' => true, 'in_app' => true, 'whatsapp' => false],
        ]);

        Artisan::call('briefing:send-daily');

        Mail::assertSent(DailySiteBriefingMail::class, 1);
        Mail::assertSent(DailySiteBriefingMail::class, fn (DailySiteBriefingMail $mail) => $mail->project->id === $dueProject->id);
        Notification::assertSentTimes(OperationalReminderNotification::class, 1);

        $this->assertDatabaseCount('project_daily_briefing_logs', 1);
        $log = ProjectDailyBriefingLog::first();
        $this->assertSame($dueProject->id, $log->project_id);
        $this->assertSame('green', $log->risk_level);
        $this->assertSame(0, $log->blockers_count);
        $this->assertSame(1, $log->recipients_count);
        $this->assertIsArray($log->snapshot);
        $this->assertSame('green', $log->snapshot['risk_level']);

        // Running again the same day must not double-send (idempotency guard).
        Artisan::call('briefing:send-daily');

        Mail::assertSent(DailySiteBriefingMail::class, 1);
        Notification::assertSentTimes(OperationalReminderNotification::class, 1);
        $this->assertDatabaseCount('project_daily_briefing_logs', 1);
    }

    public function test_command_skips_disabled_settings(): void
    {
        Mail::fake();
        Notification::fake();

        $owner = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);
        $project = $this->createProject($owner, 'Proiect Dezactivat');
        ProjectDailyBriefingSetting::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'enabled' => false,
            'send_time' => now('Europe/Bucharest')->subMinute()->format('H:i'),
            'recipient_user_ids' => [$owner->id],
            'detail_level' => 'complet',
            'channels' => ['email' => true, 'in_app' => true, 'whatsapp' => false],
        ]);

        Artisan::call('briefing:send-daily');

        Mail::assertNothingSent();
        Notification::assertNothingSent();
    }

    private function createProject(User $user, string $name): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => $name . ' Client',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
        ]);
    }
}
