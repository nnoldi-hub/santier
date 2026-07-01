<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QualityReportsAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_reports_alias_uses_quality_checks_index(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedProjectContext($user);

        $response = $this->actingAs($user)->get('/rapoarte-calitate');

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('QualityChecks/Index');
        });
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Rapoarte Calitate',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Rapoarte Calitate',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa control calitate',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 20,
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
