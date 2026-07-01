<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QualityChecksTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_check_can_be_created(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/quality-checks', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare finisaj pereti',
            'description' => 'Control planeitate pe toate camerele.',
            'check_type' => 'execution',
            'status' => 'pending',
            'planned_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect('/quality-checks');

        $this->assertDatabaseHas('quality_checks', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare finisaj pereti',
            'check_type' => 'execution',
            'status' => 'pending',
        ]);
    }

    public function test_quality_checks_index_can_filter_by_status(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare conforma',
            'check_type' => 'materials',
            'status' => 'passed',
        ]);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare neconforma',
            'check_type' => 'execution',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($user)->get('/quality-checks?status=failed');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('QualityChecks/Index')
            ->where('checks.data.0.title', 'Verificare neconforma')
        );
    }

    public function test_quality_check_rejects_phase_not_in_selected_project(): void
    {
        $user = $this->createOnboardedUser();
        [$projectA, $phaseA] = $this->seedProjectContext($user);

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
            ->from('/quality-checks/create')
            ->post('/quality-checks', [
                'project_id' => $projectB->id,
                'phase_id' => $phaseA->id,
                'title' => 'Verificare invalida',
                'check_type' => 'execution',
                'status' => 'pending',
            ]);

        $response->assertRedirect('/quality-checks/create');
        $response->assertSessionHasErrors('phase_id');

        $this->assertDatabaseMissing('quality_checks', [
            'title' => 'Verificare invalida',
        ]);
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Verificari',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Verificari',
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
