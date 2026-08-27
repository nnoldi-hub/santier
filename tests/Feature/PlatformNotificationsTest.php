<?php

namespace Tests\Feature;

use App\Models\PilotInvite;
use App\Models\User;
use App\Support\InboundEmailMapper;
use App\Support\PilotInviteReplyImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_trial_request_notifies_superadmin(): void
    {
        $admin = $this->createSuperadmin();

        $this->post(route('demo-request.store'), [
            'company_name' => 'Firma Trial Noua',
            'contact_name' => 'Ana Test',
            'contact_email' => 'ana@example.com',
            'contact_phone' => '0712345678',
            'estimated_users' => 4,
            'customization_scope' => 'branding',
        ])->assertRedirect();

        $notification = $admin->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame('platform_trial_requested', $notification->data['event']);
        $this->assertSame('pilot_invite', $notification->data['entity_type']);
    }

    public function test_prospect_reply_notifies_superadmin(): void
    {
        $admin = $this->createSuperadmin();
        $invite = PilotInvite::query()->create([
            'tenant_id' => 1,
            'company_name' => 'Firma Raspuns',
            'contact_email' => 'prospect@example.com',
            'status' => 'contacted',
            'invited_at' => now(),
        ]);

        $imported = PilotInviteReplyImporter::import(InboundEmailMapper::map([
            'from_email' => 'prospect@example.com',
            'from_name' => 'Prospect',
            'subject' => 'Re: Modulia',
            'body_text' => 'Vreau sa discutam despre trial.',
            'body_html' => null,
            'message_id' => 'platform-reply@example.com',
            'in_reply_to' => null,
            'date' => now()->toDateTimeString(),
        ]));

        $this->assertTrue($imported);
        $notification = $admin->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame('platform_prospect_reply', $notification->data['event']);
        $this->assertSame($invite->id, $notification->data['entity_id']);
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'email' => 'platform-alerts@example.com',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
