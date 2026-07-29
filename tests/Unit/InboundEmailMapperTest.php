<?php

namespace Tests\Unit;

use App\Support\InboundEmailMapper;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class InboundEmailMapperTest extends TestCase
{
    public function test_prefers_plain_text_body_over_html(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => 'Prospect Test',
            'subject' => 'Re: Modulia',
            'body_text' => "Buna,\nSuna bine.",
            'body_html' => '<p>Buna,</p><p>Suna bine.</p>',
            'message_id' => 'abc123@example.com',
            'in_reply_to' => null,
            'date' => '2026-07-28 10:00:00',
        ]);

        $this->assertSame("Buna,\nSuna bine.", $mapped['body']);
    }

    public function test_strips_html_tags_when_no_plain_text_available(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => null,
            'body_html' => '<p>Buna</p><p>Suna bine.</p>',
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame('BunaSuna bine.', $mapped['body']);
    }

    public function test_normalizes_message_id_by_trimming_angle_brackets(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => '<abc123@example.com>',
            'in_reply_to' => '<parent456@example.com>',
            'date' => null,
        ]);

        $this->assertSame('abc123@example.com', $mapped['message_id']);
        $this->assertSame('parent456@example.com', $mapped['in_reply_to_message_id']);
    }

    public function test_blank_message_id_normalizes_to_null(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => '   ',
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertNull($mapped['message_id']);
        $this->assertNull($mapped['in_reply_to_message_id']);
    }

    public function test_parses_a_valid_date_string(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => '2026-07-20 12:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $mapped['occurred_at']);
        $this->assertSame('2026-07-20 12:30:00', $mapped['occurred_at']->toDateTimeString());
    }

    public function test_converts_a_date_with_explicit_offset_to_utc(): void
    {
        // Confirmed live: an email's Date header with a +03:00 offset (Romania
        // summer time) was stored without converting to UTC, making replies
        // appear hours "in the future" relative to the import timestamp.
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => 'Tue, 28 Jul 2026 18:41:44 +0300',
        ]);

        $this->assertTrue($mapped['occurred_at']->isUtc());
        $this->assertSame('2026-07-28 15:41:44', $mapped['occurred_at']->toDateTimeString());
    }

    public function test_converts_a_datetime_object_with_offset_to_utc(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => new \DateTimeImmutable('2026-07-28 18:41:44', new \DateTimeZone('+03:00')),
        ]);

        $this->assertTrue($mapped['occurred_at']->isUtc());
        $this->assertSame('2026-07-28 15:41:44', $mapped['occurred_at']->toDateTimeString());
    }

    public function test_falls_back_to_now_when_date_is_unparseable(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => 'not a real date',
        ]);

        $this->assertInstanceOf(Carbon::class, $mapped['occurred_at']);
        $this->assertTrue($mapped['occurred_at']->diffInSeconds(Carbon::now()) < 5);
    }

    public function test_direction_is_always_inbound(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => 'text',
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame('inbound', $mapped['direction']);
    }

    public function test_strips_outlook_style_quoted_reply(): void
    {
        $bodyText = "Buna. Multumesc\n"
            . "________________________________\n"
            . "From: Modulia <vanzari@modulia.ro>\n"
            . "Sent: Tuesday, July 28, 2026 3:27 PM\n"
            . "To: Noldi NYIKORA <noldi.nyikora@nks-cables.ro>\n"
            . "Subject: Re: Modulia - Nk Smart Cables SRL\n\n"
            . "Buna\n\nCu stima,\n\nSuper Admin\nEchipa Modulia";

        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => $bodyText,
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame('Buna. Multumesc', $mapped['body']);
    }

    public function test_strips_gmail_style_quoted_reply(): void
    {
        $bodyText = "Suna bine, va rog trimiteti detalii.\n\n"
            . "On Tue, Jul 28, 2026 at 3:27 PM Modulia <vanzari@modulia.ro> wrote:\n"
            . "> Buna\n> Cu stima,\n> Super Admin";

        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => $bodyText,
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame('Suna bine, va rog trimiteti detalii.', $mapped['body']);
    }

    public function test_strips_romanian_gmail_style_quoted_reply(): void
    {
        // Real Gmail RO format observed live: starts with the weekday
        // abbreviation, not "Pe"/"În" as originally assumed.
        $bodyText = "Salutare sunt interesat\n\n"
            . "mar., 28 iul. 2026, 16:12 Mihaita Purghel <instalatiisanitaresigure@gmail.com> a scris:\n"
            . "Cu drag";

        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => $bodyText,
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame('Salutare sunt interesat', $mapped['body']);
    }

    public function test_strips_romanian_gmail_quote_with_no_separating_newline(): void
    {
        // Real Gmail mobile reply observed live: the quote attribution ran
        // into the same line as the prospect's own text (no newline before
        // it), so a line-start-anchored pattern alone missed it.
        $bodyText = 'Puteti sa imi trimiteti oferta?'
            . "\n\nMultumesc mie., 29 iul. 2026, 08:33 Mihaita Purghel <instalatiisanitaresigure@gmail.com> a scris:\nCu drag";

        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => $bodyText,
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame("Puteti sa imi trimiteti oferta?\n\nMultumesc", $mapped['body']);
    }

    public function test_strips_romanian_gmail_quote_separated_by_non_breaking_spaces(): void
    {
        $bodyText = "Multumesc\n\n"
            . "mie.,\u{00A0}29\u{00A0}iul.\u{00A0}2026,\u{00A0}08:33 Mihaita Purghel <a@b.ro> a scris:\nCu drag";

        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => $bodyText,
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame('Multumesc', $mapped['body']);
    }

    public function test_leaves_a_reply_without_any_quote_markers_untouched(): void
    {
        $mapped = InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => null,
            'subject' => null,
            'body_text' => "Buna ziua,\nMultumesc pentru informatii, revin cu detalii saptamana viitoare.",
            'body_html' => null,
            'message_id' => null,
            'in_reply_to' => null,
            'date' => null,
        ]);

        $this->assertSame("Buna ziua,\nMultumesc pentru informatii, revin cu detalii saptamana viitoare.", $mapped['body']);
    }
}
