<?php

namespace App\Support;

use App\Models\PilotInvite;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Illuminate\Support\Str;

class PlatformNotifier
{
    public static function notifyNewTrial(PilotInvite $invite): void
    {
        self::notifyAdmins(new OperationalReminderNotification(
            event: 'platform_trial_requested',
            title: 'Cerere noua de trial',
            message: Str::limit($invite->company_name . ' - ' . $invite->contact_email, 140),
            entityType: 'pilot_invite',
            entityId: (int) $invite->id,
            projectId: null,
            projectName: null,
            url: route('admin.commercial-dashboard.index'),
            severity: 'high',
        ));
    }

    public static function notifyReply(PilotInvite $invite, array $mapped): void
    {
        self::notifyAdmins(new OperationalReminderNotification(
            event: 'platform_prospect_reply',
            title: 'Raspuns nou de la prospect',
            message: Str::limit($invite->company_name . ': ' . ($mapped['body'] ?? ''), 140),
            entityType: 'pilot_invite',
            entityId: (int) $invite->id,
            projectId: null,
            projectName: null,
            url: route('admin.commercial-dashboard.index'),
            severity: 'high',
        ));
    }

    private static function notifyAdmins(OperationalReminderNotification $notification): void
    {
        User::query()
            ->where(function ($query): void {
                $query->where('is_superadmin', true)
                    ->orWhereIn('email', config('platform.admin_emails', []));
            })
            ->get()
            ->each(fn (User $admin) => $admin->notify($notification));
    }
}
