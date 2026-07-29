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

    /**
     * Patterns marking where a quoted previous message begins in a plain-text
     * body, so replies show only what the prospect actually typed. Covers the
     * separators/headers used by Outlook and Gmail, in Romanian and English.
     */
    private const QUOTE_MARKERS = [
        '/^[_-]{8,}\s*$/mu',                                   // Outlook's horizontal rule separator
        '/^From:\s*\S.*\n(?:Sent|Date):\s*\S.*/miu',           // Outlook header block (EN)
        '/^De la:\s*\S.*\n(?:Trimis|Data):\s*\S.*/miu',        // Outlook header block (RO)
        // Gmail-style attribution lines ("On ... wrote:" / "mar., 28 iul.
        // ... a scris:") - deliberately NOT anchored to line-start with a
        // ".{0,160}" prefix: Gmail mobile sometimes runs the attribution
        // into the same line as the prospect's own text (no separating
        // newline), and a ".{0,160}" prefix would then greedily eat that
        // real text too. Anchoring directly on the distinctive "On "/
        // weekday-abbreviation token avoids both problems at once - it
        // matches whether the attribution starts its own line or not,
        // without ever consuming genuine prospect text before it.
        // "s" (dotall) so "." also bridges a hard line-wrap landing mid-way
        // through the quoted name/address (confirmed live: a mobile Gmail
        // reply wrapped exactly between "<" and the email address with a
        // bare "\r\n", which a non-dotall "." can never cross).
        '/\bOn[\s\x{00A0}].{0,160}?\bwrote:/isu',
        '/\b(?:lun|mar|mie|joi|vin|s[aâ]m|dum)\.,?[\s\x{00A0}]+\d{1,2}[\s\x{00A0}]+\w+\.?[\s\x{00A0}]+\d{4}.{0,120}?\ba scris:/isu',
        '/^>.*$/mu',                                           // classic ">" quote prefix
    ];

    private static function extractBody(?string $text, ?string $html): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            $stripped = trim(strip_tags((string) $html));
            $text = preg_replace('/\n{3,}/', "\n\n", $stripped) ?? $stripped;
        }

        return self::stripQuotedReply($text);
    }

    private static function stripQuotedReply(string $text): string
    {
        $earliestOffset = null;

        foreach (self::QUOTE_MARKERS as $pattern) {
            if (preg_match($pattern, $text, $match, PREG_OFFSET_CAPTURE) === 1) {
                $offset = $match[0][1];

                if ($earliestOffset === null || $offset < $earliestOffset) {
                    $earliestOffset = $offset;
                }
            }
        }

        if ($earliestOffset === null) {
            return $text;
        }

        $trimmed = trim(substr($text, 0, $earliestOffset));

        return $trimmed !== '' ? $trimmed : $text;
    }

    private static function normalizeMessageId(?string $messageId): ?string
    {
        $messageId = trim((string) $messageId, " \t\n\r\0\x0B<>");

        return $messageId === '' ? null : $messageId;
    }

    /**
     * Always normalized to UTC before returning, regardless of what timezone
     * the source carried (the email's own Date header, or whatever timezone
     * the IMAP client parsed it into) - storage/serialization elsewhere in
     * the app assumes UTC, so any un-normalized instant would round-trip
     * with a wrong offset (confirmed live: replies showing hours "in the
     * future" because the offset was applied twice).
     */
    private static function normalizeDate(mixed $date): Carbon
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->utc();
        }

        if ($date) {
            try {
                return Carbon::parse((string) $date)->utc();
            } catch (\Throwable) {
                // fall through to now()
            }
        }

        return Carbon::now('UTC');
    }
}
