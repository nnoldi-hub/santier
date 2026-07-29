<?php

namespace Tests\Feature;

use App\Models\PilotInvite;
use App\Models\PilotInviteMessage;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use App\Support\InboundEmailMapper;
use App\Support\PilotInviteReplyImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotInviteReplyImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_a_reply_and_notifies_the_owner(): void
    {
        $owner = $this->createOnboardedUser();
        $invite = $this->createInvite($owner, 'prospect@import-test.ro');

        $imported = PilotInviteReplyImporter::import($this->mappedReply(
            fromEmail: 'prospect@import-test.ro',
            body: 'Suna interesant, revin cu detalii.',
            messageId: 'reply-1@example.com',
        ));

        $this->assertTrue($imported);

        $this->assertDatabaseHas('pilot_invite_messages', [
            'pilot_invite_id' => $invite->id,
            'direction' => 'inbound',
            'body' => 'Suna interesant, revin cu detalii.',
            'message_id' => 'reply-1@example.com',
        ]);

        $this->assertDatabaseHas('commercial_actions', [
            'pilot_invite_id' => $invite->id,
            'action_type' => 'email',
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->last_contacted_at);

        $notification = $owner->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame(OperationalReminderNotification::class, $notification->type);
        $this->assertSame('commercial_reply_received', (string) ($notification->data['event'] ?? null));
        $this->assertSame('Suna interesant, revin cu detalii.', (string) ($notification->data['message'] ?? null));
    }

    public function test_matches_contact_email_case_insensitively(): void
    {
        $owner = $this->createOnboardedUser();
        $invite = $this->createInvite($owner, 'Prospect@Import-Test.ro');

        $imported = PilotInviteReplyImporter::import($this->mappedReply(
            fromEmail: 'prospect@import-test.ro',
            body: 'Raspuns',
        ));

        $this->assertTrue($imported);
        $this->assertDatabaseHas('pilot_invite_messages', ['pilot_invite_id' => $invite->id]);
    }

    public function test_does_not_import_the_same_message_id_twice(): void
    {
        $owner = $this->createOnboardedUser();
        $this->createInvite($owner, 'prospect@import-test.ro');

        $first = PilotInviteReplyImporter::import($this->mappedReply(
            fromEmail: 'prospect@import-test.ro',
            body: 'Primul mesaj',
            messageId: 'dup-1@example.com',
        ));
        $second = PilotInviteReplyImporter::import($this->mappedReply(
            fromEmail: 'prospect@import-test.ro',
            body: 'Primul mesaj',
            messageId: 'dup-1@example.com',
        ));

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(1, PilotInviteMessage::query()->where('message_id', 'dup-1@example.com')->count());
    }

    public function test_returns_false_when_no_matching_invite_exists(): void
    {
        $imported = PilotInviteReplyImporter::import($this->mappedReply(
            fromEmail: 'unknown@nowhere.ro',
            body: 'Nimeni nu asteapta acest mesaj',
        ));

        $this->assertFalse($imported);
        $this->assertDatabaseCount('pilot_invite_messages', 0);
    }

    public function test_returns_false_when_from_email_is_missing(): void
    {
        $imported = PilotInviteReplyImporter::import($this->mappedReply(fromEmail: null, body: 'text'));

        $this->assertFalse($imported);
    }

    public function test_does_not_notify_when_invite_has_no_owner(): void
    {
        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => null,
            'company_name' => 'Fara Responsabil SRL',
            'contact_email' => 'fara-responsabil@import-test.ro',
            'status' => 'contacted',
            'invited_at' => now(),
        ]);

        $imported = PilotInviteReplyImporter::import($this->mappedReply(
            fromEmail: 'fara-responsabil@import-test.ro',
            body: 'Raspuns',
        ));

        $this->assertTrue($imported);
        $this->assertDatabaseHas('pilot_invite_messages', ['pilot_invite_id' => $invite->id]);
    }

    private function mappedReply(?string $fromEmail, string $body, ?string $messageId = null): array
    {
        return InboundEmailMapper::map([
            'from_email' => $fromEmail,
            'from_name' => 'Prospect Test',
            'subject' => 'Re: Modulia',
            'body_text' => $body,
            'body_html' => null,
            'message_id' => $messageId,
            'in_reply_to' => null,
            'date' => now()->toDateTimeString(),
        ]);
    }

    private function createInvite(User $owner, string $contactEmail): PilotInvite
    {
        return PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $owner->id,
            'company_name' => 'Import Test SRL',
            'contact_name' => 'Ana Popescu',
            'contact_email' => $contactEmail,
            'status' => 'contacted',
            'invited_at' => now(),
        ]);
    }

    private function createOnboardedUser(): User
    {
        return User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);
    }
}
