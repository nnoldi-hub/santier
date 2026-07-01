<?php

namespace Tests\Feature;

use App\Mail\TrialLifecycleMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TrialLifecycleEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_lifecycle_command_sends_eligible_campaigns_and_logs_once(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'billing_plan' => 'pro',
            'billing_trial_ends_at' => now()->addDay(),
        ]);

        $user->forceFill([
            'created_at' => now()->subDays(10),
        ])->save();

        Artisan::call('emails:send-trial-lifecycle');

        Mail::assertSent(TrialLifecycleMail::class, 4);

        $this->assertDatabaseHas('email_campaign_logs', [
            'user_id' => $user->id,
            'campaign_key' => 'welcome',
        ]);

        $this->assertDatabaseHas('email_campaign_logs', [
            'user_id' => $user->id,
            'campaign_key' => 'trial_day_3',
        ]);

        $this->assertDatabaseHas('email_campaign_logs', [
            'user_id' => $user->id,
            'campaign_key' => 'trial_day_10',
        ]);

        $this->assertDatabaseHas('email_campaign_logs', [
            'user_id' => $user->id,
            'campaign_key' => 'upgrade_prompt',
        ]);

        Artisan::call('emails:send-trial-lifecycle');

        $this->assertDatabaseCount('email_campaign_logs', 4);
    }

    public function test_registration_sets_trial_plan_and_trial_end_date(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Trial User',
            'email' => 'trial-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/onboarding');

        $this->assertDatabaseHas('users', [
            'email' => 'trial-user@example.com',
            'billing_plan' => 'pro',
        ]);

        $user = User::where('email', 'trial-user@example.com')->firstOrFail();
        $this->assertNotNull($user->billing_trial_ends_at);
    }
}
