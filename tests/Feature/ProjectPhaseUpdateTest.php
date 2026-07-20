<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPhaseUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_an_existing_phase(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $response = $this->actingAs($user)->put("/projects/{$project->id}/phases/{$phase->id}", [
            'name' => 'Structura actualizata',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 40,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_phases', [
            'id' => $phase->id,
            'name' => 'Structura actualizata',
            'status' => 'in_progress',
            'progress_pct' => 40,
        ]);
    }

    public function test_user_can_set_a_buffer_on_a_phase(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $response = $this->actingAs($user)->put("/projects/{$project->id}/phases/{$phase->id}", [
            'name' => 'Structura',
            'type' => 'structura',
            'status' => 'pending',
            'progress_pct' => 0,
            'buffer_days' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_phases', ['id' => $phase->id, 'buffer_days' => 5]);
    }

    public function test_buffer_days_cannot_be_negative(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $response = $this->actingAs($user)->put("/projects/{$project->id}/phases/{$phase->id}", [
            'name' => 'Structura',
            'type' => 'structura',
            'status' => 'pending',
            'progress_pct' => 0,
            'buffer_days' => -3,
        ]);

        $response->assertSessionHasErrors('buffer_days');
    }

    public function test_user_cannot_update_a_phase_on_another_tenants_project(): void
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

        $otherOwner = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2]);
        $otherClient = Client::create([
            'tenant_id' => 2,
            'name' => 'Client Intrus SRL',
            'type' => 'company',
            'active' => true,
        ]);
        $otherProject = Project::create([
            'tenant_id' => 2,
            'client_id' => $otherClient->id,
            'created_by' => $otherOwner->id,
            'name' => 'Proiect Intrus',
            'status' => 'active',
            'total_budget' => 1000,
            'start_date' => now()->toDateString(),
        ]);
        $otherPhase = ProjectPhase::create([
            'project_id' => $otherProject->id,
            'name' => 'Etapa intrusa',
            'type' => 'structura',
            'order' => 1,
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $response = $this->actingAs($user)->put("/projects/{$otherProject->id}/phases/{$otherPhase->id}", [
            'name' => 'Incercare modificare',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 50,
        ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('project_phases', ['id' => $otherPhase->id, 'name' => 'Etapa intrusa']);
    }

    public function test_phase_must_belong_to_the_given_project(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $otherProject = $this->createProject($user, 'Alt Proiect');
        $phaseOnOtherProject = ProjectPhase::create([
            'project_id' => $otherProject->id,
            'name' => 'Etapa pe alt proiect',
            'type' => 'structura',
            'order' => 1,
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $response = $this->actingAs($user)->put("/projects/{$project->id}/phases/{$phaseOnOtherProject->id}", [
            'name' => 'Incercare',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 50,
        ]);

        $response->assertNotFound();
    }

    private function createProject(User $user, string $name = 'Proiect Test'): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => $name . ' Client',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
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
