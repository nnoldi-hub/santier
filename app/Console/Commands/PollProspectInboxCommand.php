<?php

namespace App\Console\Commands;

use App\Models\CommercialAction;
use App\Models\PilotInvite;
use App\Models\PilotInviteMessage;
use App\Support\InboundEmailMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client as ClientFacade;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Message;

class PollProspectInboxCommand extends Command
{
    protected $signature = 'emails:poll-prospect-inbox';

    protected $description = 'Read the shared sales mailbox and thread new replies into the matching pilot invite';

    public function handle(): int
    {
        $client = ClientFacade::account('default');

        try {
            $client->connect();
        } catch (ConnectionFailedException $e) {
            Log::error('emails:poll-prospect-inbox - IMAP connection failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $folder = $client->getFolder('INBOX');

        $messages = $folder->messages()
            ->whereSince(now()->subDays(3))
            ->get();

        $imported = 0;

        foreach ($messages as $message) {
            if ($this->importMessage($message)) {
                $imported++;
            }
        }

        $this->info("Checked {$messages->count()} message(s), imported {$imported} new reply/replies.");

        return self::SUCCESS;
    }

    private function importMessage(Message $message): bool
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => $message->from->first()?->mail,
            'from_name' => $message->from->first()?->personal,
            'subject' => (string) $message->subject,
            'body_text' => $message->hasTextBody() ? $message->getTextBody() : null,
            'body_html' => $message->hasHTMLBody() ? $message->getHTMLBody() : null,
            'message_id' => $message->message_id?->first(),
            'in_reply_to' => $message->in_reply_to?->first(),
            'date' => $message->date?->first(),
        ]);

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

        return true;
    }
}
