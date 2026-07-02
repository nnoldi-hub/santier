<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TasksFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_list_can_be_filtered_by_status_and_project(): void
    {
        $this->seed(IamSeeder::class);

        $user = $this->createTenantUser('tasks.filter@santier.local');
        $projectA = $this->createProject($user, 'Proiect A');
        $projectB = $this->createProject($user, 'Proiect B');

        $this->createTask($user, $projectA, 'Task deschis', 'todo', 'high');
        $selectedTask = $this->createTask($user, $projectB, 'Task final', 'done', 'medium');
        $this->createTask($user, $projectB, 'Task anulat', 'cancelled', 'low');

        $this->actingAs($user)
            ->get(route('tasks.index', [
                'status' => 'done',
                'project_id' => $projectB->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->has('tasks.data', 1)
                ->where('tasks.data.0.id', $selectedTask->id)
                ->where('filters', function ($filters) use ($projectB): bool {
                    return $filters['status'] === 'done'
                        && (int) $filters['project_id'] === (int) $projectB->id;
                })
            );
    }

    public function test_task_status_update_sets_completion_timestamp_for_done(): void
    {
        $this->seed(IamSeeder::class);

        $user = $this->createTenantUser('tasks.status@santier.local');
        $project = $this->createProject($user, 'Proiect Status');
        $task = $this->createTask($user, $project, 'Task de status', 'todo', 'high');

        $this->actingAs($user)
            ->patch(route('tasks.status', $task), [
                'status' => 'done',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    private function createTenantUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createProject(User $user, string $name): Project
    {
        return Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function createTask(User $user, Project $project, string $title, string $status, string $priority): Task
    {
        return Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => $title,
            'description' => 'Descriere task',
            'status' => $status,
            'priority' => $priority,
            'deadline' => now()->addDays(2)->toDateTimeString(),
        ]);
    }
}
