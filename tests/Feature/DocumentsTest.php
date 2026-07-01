<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_can_be_created_with_file_upload(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $stage, $contractor] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/documents', [
            'title' => 'Factura avans etapa 1',
            'type' => 'invoice',
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'amount' => 3500,
            'issued_at' => '2026-07-01',
            'payment_status' => 'unpaid',
            'notes' => 'Scadenta 15 zile',
            'attachment' => UploadedFile::fake()->create('factura.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect('/documents');

        $this->assertDatabaseHas('documents', [
            'tenant_id' => 1,
            'title' => 'Factura avans etapa 1',
            'type' => 'invoice',
            'payment_status' => 'unpaid',
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
        ]);

        $document = Document::query()->first();
        $this->assertNotNull($document?->file_path);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_documents_can_be_filtered_by_payment_status(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $stage, $contractor] = $this->seedProjectContext($user);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura neachitata',
            'type' => 'invoice',
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'amount' => 1000,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura achitata',
            'type' => 'invoice',
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'amount' => 2000,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get('/documents?payment_status=unpaid');

        $response->assertOk();
        $response->assertSee('Factura neachitata');
        $response->assertDontSee('Factura achitata');
    }

    public function test_documents_index_contains_summary_by_stage(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $stage, $contractor] = $this->seedProjectContext($user);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Deviz etapa',
            'type' => 'estimate',
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'amount' => 1500,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'partial',
        ]);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Oferta etapa',
            'type' => 'offer',
            'project_id' => $project->id,
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'amount' => 500,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get('/documents');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Documents/Index')
            ->where('summaryByStage.0.stage_name', $stage->name)
            ->where('summaryByStage.0.documents_count', 2)
            ->where('summaryByStage.0.total_amount', 2000)
            ->where('financialInsights.paid_count', 0)
            ->where('financialInsights.partial_count', 1)
            ->where('financialInsights.unpaid_count', 1)
            ->where('financialInsights.total_unpaid_amount', 2000)
        );
    }

    public function test_document_rejects_stage_that_does_not_belong_to_selected_project(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$projectA, $stageA, $contractor] = $this->seedProjectContext($user);

        $clientB = Client::create([
            'tenant_id' => 1,
            'name' => 'Client B',
            'type' => 'company',
            'active' => true,
        ]);

        $projectB = Project::create([
            'tenant_id' => 1,
            'client_id' => $clientB->id,
            'created_by' => $user->id,
            'name' => 'Proiect B',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->from('/documents/create')
            ->post('/documents', [
                'title' => 'Factura invalida',
                'type' => 'invoice',
                'project_id' => $projectB->id,
                'stage_id' => $stageA->id,
                'contractor_id' => $contractor->id,
                'amount' => 1200,
                'issued_at' => '2026-07-01',
                'payment_status' => 'unpaid',
            ]);

        $response->assertRedirect('/documents/create');
        $response->assertSessionHasErrors('stage_id');

        $this->assertDatabaseMissing('documents', [
            'title' => 'Factura invalida',
        ]);
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Documente',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Documente',
            'status' => 'active',
        ]);

        $stage = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa Bugete',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 10,
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Doc',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        return [$project, $stage, $contractor];
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
