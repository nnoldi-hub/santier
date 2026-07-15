<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Client;
use App\Models\Document;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DocumentBranding;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_tenant_is_forced_to_classic_even_if_modern_was_saved(): void
    {
        $this->createTenant('starter');
        AppSetting::setValues(['document_template' => 'modern'], 1);

        $branding = DocumentBranding::resolve(1);

        $this->assertSame('classic', $branding['document_template']);
    }

    public function test_pro_tenant_keeps_modern_when_saved(): void
    {
        $this->createTenant('pro');
        AppSetting::setValues(['document_template' => 'modern'], 1);

        $branding = DocumentBranding::resolve(1);

        $this->assertSame('modern', $branding['document_template']);
    }

    public function test_pro_tenant_defaults_to_classic_when_nothing_saved(): void
    {
        $this->createTenant('pro');

        $branding = DocumentBranding::resolve(1);

        $this->assertSame('classic', $branding['document_template']);
    }

    public function test_quote_pdf_renders_for_classic_and_modern_template(): void
    {
        $user = $this->createOnboardedUser('pro');
        $quote = $this->createQuote($user);

        AppSetting::setValues(['document_template' => 'classic'], 1);
        $this->actingAs($user)->get("/quotes/{$quote->id}/pdf")->assertStatus(200);

        AppSetting::setValues(['document_template' => 'modern'], 1);
        $this->actingAs($user)->get("/quotes/{$quote->id}/pdf")->assertStatus(200);
    }

    public function test_document_pdf_renders_for_classic_and_modern_template(): void
    {
        $user = $this->createOnboardedUser('pro');
        $document = $this->createDocument($user);

        AppSetting::setValues(['document_template' => 'classic'], 1);
        $this->actingAs($user)->get("/documents/{$document->id}/pdf")->assertStatus(200);

        AppSetting::setValues(['document_template' => 'modern'], 1);
        $this->actingAs($user)->get("/documents/{$document->id}/pdf")->assertStatus(200);
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

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Test SRL',
            'type' => 'company',
            'email' => 'client@test.ro',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Sablon',
            'status' => 'active',
            'total_budget' => 10000,
            'start_date' => now()->toDateString(),
        ]);
    }

    private function createQuote(User $user): Quote
    {
        $project = $this->createProject($user);

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

    private function createDocument(User $user): Document
    {
        $project = $this->createProject($user);

        return Document::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'type' => 'proc_verbal_receptie',
            'amount' => 5000,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'pending',
            'title' => 'Proces verbal Test',
        ]);
    }
}
