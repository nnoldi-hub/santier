<?php

namespace App\Console\Commands;

use App\Support\InboundEmailMapper;
use App\Support\PilotInviteReplyImporter;
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
            if (PilotInviteReplyImporter::import($this->extractFields($message))) {
                $imported++;
            }
        }

        $this->info("Checked {$messages->count()} message(s), imported {$imported} new reply/replies.");

        return self::SUCCESS;
    }

    private function extractFields(Message $message): array
    {
        return InboundEmailMapper::map([
            'from_email' => $message->from->first()?->mail,
            'from_name' => $message->from->first()?->personal,
            'subject' => (string) $message->subject,
            'body_text' => $message->hasTextBody() ? $message->getTextBody() : null,
            'body_html' => $message->hasHTMLBody() ? $message->getHTMLBody() : null,
            'message_id' => $message->message_id?->first(),
            'in_reply_to' => $message->in_reply_to?->first(),
            'date' => $message->date?->first(),
        ]);
    }
}
