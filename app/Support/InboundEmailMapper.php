<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class InboundEmailMapper
{
    /**
     * Normalizes a raw, already-fetched email message into the array shape
     * expected by PilotInviteMessage::create() (minus tenant_id/pilot_invite_id,
     * which the caller fills in once it has matched a prospect). Kept free of
     * any IMAP client dependency so it can be unit tested with plain arrays.
     *
     * @param  array{from_email: ?string, from_name: ?string, subject: ?string, body_text: ?string, body_html: ?string, message_id: ?string, in_reply_to: ?string, date: mixed}  $raw
     */
    public static function map(array $raw): array
    {
        return [
            'direction' => 'inbound',
            'from_email' => $raw['from_email'] ?? null,
            'from_name' => $raw['from_name'] ?? null,
            'subject' => $raw['subject'] ?? null,
            'body' => self::extractBody($raw['body_text'] ?? null, $raw['body_html'] ?? null),
            'message_id' => self::normalizeMessageId($raw['message_id'] ?? null),
            'in_reply_to_message_id' => self::normalizeMessageId($raw['in_reply_to'] ?? null),
            'occurred_at' => self::normalizeDate($raw['date'] ?? null),
        ];
    }

    private static function extractBody(?string $text, ?string $html): string
    {
        $text = trim((string) $text);

        if ($text !== '') {
            return $text;
        }

        $stripped = trim(strip_tags((string) $html));

        return preg_replace('/\n{3,}/', "\n\n", $stripped) ?? $stripped;
    }

    private static function normalizeMessageId(?string $messageId): ?string
    {
        $messageId = trim((string) $messageId, " \t\n\r\0\x0B<>");

        return $messageId === '' ? null : $messageId;
    }

    private static function normalizeDate(mixed $date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }

        if ($date) {
            try {
                return Carbon::parse((string) $date);
            } catch (\Throwable) {
                // fall through to now()
            }
        }

        return Carbon::now();
    }
}
