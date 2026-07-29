<?php

namespace App\Support;

use App\Models\CommercialAction;
use App\Models\PilotInvite;
use App\Models\PilotInviteMessage;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Persists an already-mapped inbound email (see InboundEmailMapper) as a
 * PilotInviteMessage and notifies the invite's owner - kept free of any IMAP
 * client dependency so the matching/idempotency/notification logic can be
 * tested directly with plain arrays, unlike the IMAP fetch loop itself.
 */
class PilotInviteReplyImporter
{
    public static function import(array $mapped): bool
    {
        if ($mapped['message_id'] && PilotInviteMessage::query()->where('message_id', $mapped['message_id'])->exists()) {
            return false;
        }

        if (!$mapped['from_email']) {
            return false;
        }

        $invite = PilotInvite::query()
            ->whereRaw('lower(contact_email) = ?', [strtolower($mapped['from_email'])])
            ->latest('id')
            ->first();

        if (!$invite) {
            Log::warning("emails:poll-prospect-inbox - no pilot invite found for reply from {$mapped['from_email']}");

            return false;
        }

        PilotInviteMessage::create([
            'tenant_id' => $invite->tenant_id,
            'pilot_invite_id' => $invite->id,
            ...$mapped,
        ]);

        CommercialAction::create([
            'tenant_id' => $invite->tenant_id,
            'pilot_invite_id' => $invite->id,
            'actor_id' => null,
            'action_type' => 'email',
            'notes' => 'Raspuns primit pe email de la ' . ($mapped['from_name'] ?: $mapped['from_email']) . '.',
        ]);

        $invite->update(['last_contacted_at' => now()]);

        self::notifyOwner($invite, $mapped);

        return true;
    }

    private static function notifyOwner(PilotInvite $invite, array $mapped): void
    {
        $owner = $invite->owner;

        if (!$owner instanceof User) {
            return;
        }

        $owner->notify(new OperationalReminderNotification(
            event: 'commercial_reply_received',
            title: 'Raspuns nou de la ' . $invite->company_name,
            message: Str::limit($mapped['body'], 140),
            entityType: 'pilot_invite',
            entityId: (int) $invite->id,
            projectId: null,
            projectName: null,
            url: route('pilot-invites.show', $invite->id),
            severity: 'medium',
        ));
    }
}
