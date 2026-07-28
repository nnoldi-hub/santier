<?php

namespace Tests\Feature;

use App\Mail\PilotInviteThreadReplyMail;
use App\Models\CommercialAction;
use App\Models\PilotInvite;
use App\Models\PilotInviteMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PilotInviteThreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_a_thread_message_to_a_prospect(): void
    {
        Mail::fake();

        $user = $this->createOnboardedUser();
        $invite = $this->createInvite($user, 'prospect@thread-test.ro');

        $response = $this->actingAs($user)
            ->from("/pilot-invites/{$invite->id}")
            ->post("/pilot-invites/{$invite->id}/messages", [
                'body' => 'Multumim pentru interes, revenim cu detalii.',
            ]);

        $response->assertRedirect("/pilot-invites/{$invite->id}");

        Mail::assertSent(PilotInviteThreadReplyMail::class, function (PilotInviteThreadReplyMail $mail) use ($invite) {
            return $mail->hasTo($invite->contact_email)
                && $mail->invite->is($invite)
                && $mail->body === 'Multumim pentru interes, revenim cu detalii.';
        });

        $this->assertDatabaseHas('pilot_invite_messages', [
            'pilot_invite_id' => $invite->id,
            'direction' => 'outbound',
            'body' => 'Multumim pentru interes, revenim cu detalii.',
            'actor_id' => $user->id,
        ]);

        $this->assertDatabaseHas('commercial_actions', [
            'pilot_invite_id' => $invite->id,
            'action_type' => 'email',
        ]);

        $invite->refresh();
        $this->assertNotNull($invite->last_contacted_at);
    }

    public function test_sending_a_message_requires_a_contact_email(): void
    {
        Mail::fake();

        $user = $this->createOnboardedUser();
        $invite = $this->createInvite($user, '');

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$invite->id}/messages", ['body' => 'Salut']);

        $response->assertStatus(422);
        Mail::assertNothingSent();
    }

    public function test_sending_a_message_requires_a_body(): void
    {
        Mail::fake();

        $user = $this->createOnboardedUser();
        $invite = $this->createInvite($user, 'prospect@thread-test.ro');

        $response = $this->actingAs($user)
            ->post("/pilot-invites/{$invite->id}/messages", ['body' => '']);

        $response->assertSessionHasErrors('body');
        Mail::assertNothingSent();
    }

    public function test_user_cannot_message_a_prospect_from_another_tenant(): void
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
            ->post("/pilot-invites/{$otherInvite->id}/messages", ['body' => 'Salut']);

        $response->assertNotFound();
        Mail::assertNothingSent();
    }

    public function test_show_page_renders_a_merged_chronological_timeline(): void
    {
        $user = $this->createOnboardedUser();
        $invite = $this->createInvite($user, 'prospect@thread-test.ro');

        $action = CommercialAction::create([
            'tenant_id' => 1,
            'pilot_invite_id' => $invite->id,
            'actor_id' => $user->id,
            'action_type' => 'apel',
            'notes' => 'Prima discutie telefonica',
        ]);
        $action->forceFill(['created_at' => '2026-07-20 09:00:00'])->save();

        PilotInviteMessage::create([
            'tenant_id' => 1,
            'pilot_invite_id' => $invite->id,
            'direction' => 'inbound',
            'from_email' => $invite->contact_email,
            'from_name' => 'Prospect Test',
            'subject' => 'Re: Modulia',
            'body' => 'Suna interesant, trimiteti detalii.',
            'message_id' => 'inbound-1@example.com',
            'occurred_at' => '2026-07-21 10:00:00',
        ]);

        $response = $this->actingAs($user)->get("/pilot-invites/{$invite->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('PilotInvites/Show')
            ->where('invite.company_name', $invite->company_name)
            ->has('timeline', 2)
            ->where('timeline.0.kind', 'action')
            ->where('timeline.1.kind', 'message')
            ->where('timeline.1.body', 'Suna interesant, trimiteti detalii.'));
    }

    private function createInvite(User $owner, string $contactEmail): PilotInvite
    {
        return PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $owner->id,
            'company_name' => 'Thread Test SRL',
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
