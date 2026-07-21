<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefectReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_pdf_responds_for_own_tenant(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $defect = Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'reported_by' => $user->id,
            'title' => 'Defect pentru PDF',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($user)->get("/defects/{$defect->id}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_report_responds_with_a_pdf_for_a_project_with_defects(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'reported_by' => $user->id,
            'title' => 'Defect deschis',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $response = $this->actingAs($user)->get("/defects/report?project_id={$project->id}");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_and_report_are_isolated_by_tenant(): void
    {
        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $user = $this->createOnboardedUser();

        $otherClient = Client::create([
            'tenant_id' => 2,
            'name' => 'Client intrus',
            'type' => 'company',
            'active' => true,
        ]);

        $otherProject = Project::create([
            'tenant_id' => 2,
            'client_id' => $otherClient->id,
            'created_by' => $user->id,
            'name' => 'Proiect intrus',
            'status' => 'active',
        ]);

        $otherDefect = Defect::create([
            'tenant_id' => 2,
            'project_id' => $otherProject->id,
            'reported_by' => $user->id,
            'title' => 'Defect intrus',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $this->actingAs($user)->get("/defects/{$otherDefect->id}/pdf")->assertForbidden();
        $this->actingAs($user)->get("/defects/report?project_id={$otherProject->id}")->assertNotFound();
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Raport Defecte',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Raport Defecte',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa control',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 15,
        ]);

        return [$project, $phase];
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
