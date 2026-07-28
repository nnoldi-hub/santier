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
}
