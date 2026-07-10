<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageTask;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StageTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_task_can_be_created_with_user_assignee(): void
    {
        $user = $this->createOnboardedUser();
        $assignee = User::factory()->create();
        $stage = $this->seedContext($user);

        $response = $this->actingAs($user)->post('/stage-tasks', [
            'stage_id' => $stage->id,
            'title' => 'Verifica cofrajele',
            'description' => 'Control complet inainte de turnare.',
            'assignee_type' => 'user',
            'assignee_id' => $assignee->id,
            'deadline' => '2026-07-03 10:00:00',
            'status' => 'todo',
        ]);

        $response->assertRedirect('/stage-tasks');

        $this->assertDatabaseHas('stage_tasks', [
            'stage_id' => $stage->id,
            'title' => 'Verifica cofrajele',
            'assignee_type' => 'user',
            'assignee_id' => $assignee->id,
            'status' => 'todo',
        ]);
    }

    public function test_stage_tasks_index_can_filter_by_status(): void
    {
        $user = $this->createOnboardedUser();
        $stage = $this->seedContext($user);

        StageTask::create([
            'stage_id' => $stage->id,
            'title' => 'Task todo',
            'status' => 'todo',
        ]);

        StageTask::create([
            'stage_id' => $stage->id,
            'title' => 'Task done',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/stage-tasks?status=done');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('StageTasks/Index')
            ->where('tasks.data.0.title', 'Task done')
        );
    }

    private function seedContext(User $user): ProjectPhase
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Taskuri',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Taskuri',
            'status' => 'active',
        ]);

        return ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa Taskuri',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 10,
        ]);
    }

    private function createOnboardedUser(): User
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $this->seed(IamSeeder::class);

        return $user->fresh();
    }
}
