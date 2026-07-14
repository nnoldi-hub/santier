<?php

namespace Tests\Feature;

use App\Mail\PilotInvitationMail;
use App\Models\PilotInvite;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PilotInvitationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_invitation_email(): void
    {
        Mail::fake();

        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Invitatie Test SRL',
            'contact_name' => 'Ana Popescu',
            'contact_email' => 'ana@invitatietest.ro',
            'status' => 'invited',
            'invited_at' => now(),
            'last_contacted_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->from('/pilot-invites')
            ->post("/pilot-invites/{$invite->id}/send-invitation");

        $response->assertRedirect('/pilot-invites');

        Mail::assertSent(PilotInvitationMail::class, function (PilotInvitationMail $mail) use ($invite) {
            return $mail->hasTo($invite->contact_email)
                && $mail->invite->is($invite);
        });

        $this->assertDatabaseHas('commercial_actions', [
            'tenant_id' => 1,
            'pilot_invite_id' => $invite->id,
            'action_type' => 'email',
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->last_contacted_at);
    }

    public function test_invitation_requires_a_contact_email(): void
    {
        Mail::fake();

        $user = $this->createOnboardedUser();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $user->id,
            'company_name' => 'Fara Email SRL',
            'contact_email' => '',
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$invite->id}/send-invitation");

        $response->assertStatus(422);
        Mail::assertNothingSent();
    }

    public function test_user_cannot_send_invitation_for_other_tenant_invite(): void
    {
        Mail::fake();

        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $user = $this->createOnboardedUser();

        $otherOwner = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2]);
        $otherInvite = PilotInvite::create([
            'tenant_id' => 2,
            'owner_id' => $otherOwner->id,
            'company_name' => 'Alt Tenant SRL',
            'contact_email' => 'contact@alttenant.ro',
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$otherInvite->id}/send-invitation");

        $response->assertNotFound();
        Mail::assertNothingSent();
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
