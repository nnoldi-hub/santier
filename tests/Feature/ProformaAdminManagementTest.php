<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ProformaRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProformaAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_save_company_cui_and_iban_in_global_settings(): void
    {
        $superadmin = $this->createSuperadmin('superadmin@santier.local');

        $response = $this->actingAs($superadmin)->patch(route('admin.settings.update'), [
            'app_name' => 'Modulia',
            'company_name' => 'Modulia SRL',
            'support_email' => 'suport@modulia.ro',
            'sales_email' => 'vanzari@modulia.ro',
            'company_cui' => 'RO12345678',
            'company_iban' => 'RO49AAAA1B31007593840000',
            'trial_days' => 14,
        ]);

        $response->assertRedirect();

        $settings = AppSetting::allWithDefaults(config('platform.defaults', []));
        $this->assertSame('RO12345678', $settings['company_cui']);
        $this->assertSame('RO49AAAA1B31007593840000', $settings['company_iban']);
    }

    public function test_non_admin_cannot_view_proforma_requests(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.proforma-requests.index'));

        $response->assertForbidden();
    }

    public function test_superadmin_can_view_proforma_requests_list(): void
    {
        $superadmin = $this->createSuperadmin('superadmin@santier.local');
        $this->createProformaRequest(['company_name' => 'Constructii Andrei SRL']);

        $response = $this->actingAs($superadmin)->get(route('admin.proforma-requests.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ProformaRequests')
            ->has('requests', 1)
            ->where('requests.0.company_name', 'Constructii Andrei SRL')
            ->where('requests.0.status', 'sent'));
    }

    public function test_superadmin_can_mark_a_proforma_request_as_paid(): void
    {
        $superadmin = $this->createSuperadmin('superadmin@santier.local');
        $proformaRequest = $this->createProformaRequest();

        $response = $this->actingAs($superadmin)->patch(route('admin.proforma-requests.mark-paid', $proformaRequest->id));

        $response->assertRedirect();
        $this->assertSame('paid', $proformaRequest->fresh()->status);
    }

    public function test_non_admin_cannot_mark_a_proforma_request_as_paid(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
        $proformaRequest = $this->createProformaRequest();

        $response = $this->actingAs($user)->patch(route('admin.proforma-requests.mark-paid', $proformaRequest->id));

        $response->assertForbidden();
        $this->assertSame('sent', $proformaRequest->fresh()->status);
    }

    private function createSuperadmin(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createProformaRequest(array $overrides = []): ProformaRequest
    {
        return ProformaRequest::create(array_merge([
            'tenant_id' => 1,
            'company_name' => 'Firma Test SRL',
            'company_cui' => 'RO87654321',
            'contact_name' => 'Andrei Pop',
            'contact_email' => 'andrei@firma.ro',
            'contact_phone' => '0722123456',
            'plan' => 'pro',
            'interval' => 'monthly',
            'discount_pct' => 20,
            'status' => 'sent',
            'sent_at' => now(),
        ], $overrides));
    }
}
