<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WbsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarded_user_can_open_wbs_index(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'WBS Project',
            'status' => 'active',
        ]);

        ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Trasare si pregatire',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 35,
        ]);

        $response = $this->actingAs($user)->get('/wbs');

        $response->assertOk();
        $response->assertSee('Trasare si pregatire');
        $response->assertSee('WBS Project');
    }

    public function test_wbs_can_be_filtered_by_contractor(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Project cu contractori',
            'status' => 'active',
        ]);

        $wanted = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor A',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $other = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor B',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa A',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
            'contractor_id' => $wanted->id,
        ]);

        ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa B',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
            'contractor_id' => $other->id,
        ]);

        $response = $this->actingAs($user)->get('/wbs?contractor_id=' . $wanted->id);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Wbs/Index')
            ->where('phases.data.0.name', 'Etapa A')
            ->where('phases.data', fn ($items) => count($items) === 1)
        );
    }

    public function test_wbs_quick_update_can_set_sub_stage_and_progress(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Project WBS update',
            'status' => 'active',
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Update',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $parent = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa parinte',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $child = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa copil',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $response = $this->actingAs($user)->patch('/wbs/phases/' . $child->id, [
            'status' => 'in_progress',
            'progress_pct' => 55,
            'contractor_id' => $contractor->id,
            'parent_id' => $parent->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('project_phases', [
            'id' => $child->id,
            'status' => 'in_progress',
            'progress_pct' => 55,
            'contractor_id' => $contractor->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_wbs_prevents_cyclic_parent_assignment(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Project cycle guard',
            'status' => 'active',
        ]);

        $parent = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Parinte',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $child = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Copil',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
            'parent_id' => $parent->id,
        ]);

        $response = $this->actingAs($user)
            ->from('/wbs')
            ->patch('/wbs/phases/' . $parent->id, [
                'status' => 'pending',
                'progress_pct' => 0,
                'contractor_id' => null,
                'parent_id' => $child->id,
            ]);

        $response->assertRedirect('/wbs');

        $this->assertDatabaseHas('project_phases', [
            'id' => $parent->id,
            'parent_id' => null,
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
