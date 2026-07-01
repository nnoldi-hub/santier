<?php

namespace App\Console\Commands;

use App\Jobs\RunExportSubscriptionJob;
use App\Models\ExportSubscription;
use Illuminate\Console\Command;

class RunScheduledExportsCommand extends Command
{
    protected $signature = 'exports:run-scheduled';

    protected $description = 'Dispatch scheduled export subscriptions that are due';

    public function handle(): int
    {
        $dueSubscriptions = ExportSubscription::query()
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->get(['id', 'name']);

        if ($dueSubscriptions->isEmpty()) {
            $this->info('No due export subscriptions.');
            return self::SUCCESS;
        }

        foreach ($dueSubscriptions as $subscription) {
            RunExportSubscriptionJob::dispatch($subscription->id);
            $this->info('Dispatched export subscription: ' . $subscription->name);
        }

        return self::SUCCESS;
    }
}
