<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_report_responds_with_a_pdf_for_a_project_with_checks(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare conforma',
            'check_type' => 'execution',
            'status' => 'passed',
        ]);

        $response = $this->actingAs($user)->get("/quality-checks/report?project_id={$project->id}");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_project_report_is_isolated_by_tenant(): void
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

        $response = $this->actingAs($user)->get("/quality-checks/report?project_id={$otherProject->id}");

        $response->assertNotFound();
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Raport',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Raport',
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
