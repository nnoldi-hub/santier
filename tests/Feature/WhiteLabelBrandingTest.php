<?php

namespace Tests\Feature;

use App\Mail\QuoteSentMail;
use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use App\Support\DocumentBranding;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhiteLabelBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_enterprise_tenant_without_custom_branding_gets_blanked_modulia_defaults(): void
    {
        $this->createTenant('enterprise');

        $branding = DocumentBranding::resolve(1);

        $this->assertTrue($branding['white_label']);
        $this->assertSame('', $branding['company_name']);
        $this->assertSame('', $branding['document_logo_url']);
    }

    public function test_free_tenant_keeps_modulia_defaults(): void
    {
        $this->createTenant('free');

        $branding = DocumentBranding::resolve(1);

        $this->assertFalse($branding['white_label']);
        $this->assertSame('Modulia', $branding['company_name']);
    }

    public function test_enterprise_tenant_with_custom_branding_keeps_its_own_values(): void
    {
        $this->createTenant('enterprise');
        AppSetting::setValues(['company_name' => 'Firma Client SRL'], 1);

        $branding = DocumentBranding::resolve(1);

        $this->assertTrue($branding['white_label']);
        $this->assertSame('Firma Client SRL', $branding['company_name']);
    }

    public function test_quote_sent_email_omits_modulia_for_enterprise_tenant(): void
    {
        Mail::fake();
        $user = $this->createOnboardedUser('enterprise');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send");

        Mail::assertSent(QuoteSentMail::class, fn (QuoteSentMail $mail) => $mail->whiteLabel === true);
    }

    public function test_quote_sent_email_keeps_modulia_for_starter_tenant(): void
    {
        Mail::fake();
        $user = $this->createOnboardedUser('starter');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send");

        Mail::assertSent(QuoteSentMail::class, fn (QuoteSentMail $mail) => $mail->whiteLabel === false);
    }

    public function test_invite_notification_carries_white_label_flag_for_enterprise_tenant(): void
    {
        Notification::fake();
        $owner = $this->createOnboardedUser('enterprise');
        $role = Role::where('name', 'data_entry')->firstOrFail();

        $this->actingAs($owner)->post('/account/users/invite', [
            'email' => 'membru@test.ro',
            'role_id' => $role->id,
        ]);

        $invited = User::where('email', 'membru@test.ro')->firstOrFail();

        Notification::assertSentTo(
            $invited,
            UserInvitedNotification::class,
            fn (UserInvitedNotification $notification) => $notification->whiteLabel === true
        );
    }

    private function createTenant(string $plan): Tenant
    {
        return Tenant::create([
            'id' => 1,
            'name' => 'Tenant Test',
            'slug' => 'tenant-test',
            'billing_plan' => $plan,
            'status' => 'active',
            'module_flags' => [],
        ]);
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
            'name' => 'Proiect Oferta',
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
}
