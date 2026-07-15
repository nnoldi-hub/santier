<?php

namespace Tests\Feature;

use App\Mail\QuoteSentMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuoteInternalApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_unapproved_quote_is_blocked_then_unblocked_after_approval(): void
    {
        Mail::fake();
        $user = $this->createOnboardedUser('enterprise');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send");
        Mail::assertNotSent(QuoteSentMail::class);
        $this->assertSame('draft', $quote->fresh()->status);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/approve-internally")->assertRedirect();

        $quote->refresh();
        $this->assertNotNull($quote->internal_approved_at);
        $this->assertSame($user->id, $quote->internal_approved_by);
        $this->assertDatabaseHas('quote_approvals', [
            'quote_id' => $quote->id,
            'action' => 'approved',
        ]);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send")->assertRedirect();
        Mail::assertSent(QuoteSentMail::class);
    }

    public function test_cannot_approve_an_already_approved_quote(): void
    {
        $user = $this->createOnboardedUser('enterprise');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/approve-internally");
        $response = $this->actingAs($user)->patch("/quotes/{$quote->id}/approve-internally");

        $response->assertStatus(422);
    }

    public function test_unapprove_resets_and_reblocks_send(): void
    {
        Mail::fake();
        $user = $this->createOnboardedUser('enterprise');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/approve-internally");
        $this->actingAs($user)->patch("/quotes/{$quote->id}/unapprove-internally")->assertRedirect();

        $quote->refresh();
        $this->assertNull($quote->internal_approved_at);
        $this->assertNull($quote->internal_approved_by);
        $this->assertDatabaseHas('quote_approvals', [
            'quote_id' => $quote->id,
            'action' => 'unapproved',
        ]);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send");
        Mail::assertNotSent(QuoteSentMail::class);
    }

    public function test_user_without_internal_approve_permission_is_forbidden(): void
    {
        $user = $this->createOnboardedUser('enterprise');
        $quote = $this->createQuote($user);
        $user->syncRoles([Role::where('name', 'quote_specialist')->firstOrFail()]);

        $response = $this->actingAs($user)->patch("/quotes/{$quote->id}/approve-internally");

        $response->assertForbidden();
    }

    public function test_starter_tenant_sends_without_approval_and_approval_routes_are_plan_gated(): void
    {
        Mail::fake();
        $user = $this->createOnboardedUser('starter');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send")->assertRedirect();
        Mail::assertSent(QuoteSentMail::class);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/approve-internally")->assertRedirect('/dashboard');
    }

    private function createOnboardedUser(string $plan): User
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => $plan,
        ]);

        $this->seed(IamSeeder::class);
        Tenant::find(1)?->update(['billing_plan' => $plan]);

        return $user->fresh();
    }

    private function createQuote(User $user): Quote
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Test SRL',
            'type' => 'company',
            'email' => 'client@test.ro',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Aprobare',
            'status' => 'active',
            'total_budget' => 10000,
            'start_date' => now()->toDateString(),
        ]);

        return Quote::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'version' => 1,
            'title' => 'Oferta Test',
            'status' => 'draft',
            'total_net' => 1000,
            'total_tva' => 190,
            'total_gross' => 1190,
            'created_by' => $user->id,
        ]);
    }
}
