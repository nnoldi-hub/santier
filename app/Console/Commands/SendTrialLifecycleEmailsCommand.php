<?php

namespace App\Console\Commands;

use App\Mail\TrialLifecycleMail;
use App\Models\EmailCampaignLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialLifecycleEmailsCommand extends Command
{
    protected $signature = 'emails:send-trial-lifecycle';

    protected $description = 'Send automated trial lifecycle emails (welcome, day 3, day 10, upgrade prompt)';

    public function handle(): int
    {
        $users = User::query()->whereNotNull('email')->get();

        foreach ($users as $user) {
            $this->sendIfEligible($user, 'welcome', now()->greaterThanOrEqualTo($user->created_at));
            $this->sendIfEligible($user, 'trial_day_3', now()->greaterThanOrEqualTo($user->created_at->copy()->addDays(3)));
            $this->sendIfEligible($user, 'trial_day_10', now()->greaterThanOrEqualTo($user->created_at->copy()->addDays(10)));

            $upgradePromptDays = (int) config('trial_emails.upgrade_prompt_days_before_end', 2);
            $trialEndsAt = $user->billing_trial_ends_at;
            $shouldSendUpgradePrompt = $trialEndsAt !== null
                && now()->greaterThanOrEqualTo($trialEndsAt->copy()->subDays($upgradePromptDays));

            $this->sendIfEligible($user, 'upgrade_prompt', $shouldSendUpgradePrompt);
        }

        $this->info('Trial lifecycle email automation processed for ' . $users->count() . ' users.');

        return self::SUCCESS;
    }

    private function sendIfEligible(User $user, string $campaignKey, bool $isEligible): void
    {
        if (!$isEligible) {
            return;
        }

        $alreadySent = EmailCampaignLog::query()
            ->where('user_id', $user->id)
            ->where('campaign_key', $campaignKey)
            ->exists();

        if ($alreadySent) {
            return;
        }

        Mail::to($user->email)->send(new TrialLifecycleMail($user, $campaignKey));

        EmailCampaignLog::create([
            'user_id' => $user->id,
            'campaign_key' => $campaignKey,
            'sent_at' => now(),
            'meta' => [
                'billing_plan' => $user->billing_plan,
            ],
        ]);

        $this->line("Sent {$campaignKey} to {$user->email}");
    }
}
