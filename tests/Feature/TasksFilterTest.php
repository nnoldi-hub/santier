<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Material;
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

    public function test_task_list_can_be_filtered_by_critical_and_blocked(): void
    {
        $user = $this->createTenantUser('tasks.advanced@santier.local');
        $project = $this->createProject($user, 'Proiect Avansat');
        $blockedPhase = $this->createPhase($project, 'Etapa blocata', 'blocked');

        $criticalTask = $this->createTask($user, $project, 'Task critic', 'in_progress', 'high');
        $criticalTask->update(['deadline' => now()->addDay()->toDateTimeString()]);

        $blockedTask = $this->createTask($user, $project, 'Task blocat', 'in_progress', 'medium');
        $blockedTask->update(['phase_id' => $blockedPhase->id]);

        $this->actingAs($user)
            ->get(route('tasks.index', ['special_filter' => 'critical']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->has('tasks.data', 1)
                ->where('tasks.data.0.id', $criticalTask->id)
            );

        $this->actingAs($user)
            ->get(route('tasks.index', ['special_filter' => 'blocked']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tasks/Index')
                ->has('tasks.data', 1)
                ->where('tasks.data.0.id', $blockedTask->id)
            );
    }

    public function test_task_checklist_is_persisted_on_create(): void
    {
        $user = $this->createTenantUser('tasks.checklist@santier.local');
        $project = $this->createProject($user, 'Proiect Checklist');

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'title' => 'Task cu checklist',
                'description' => 'Detalii',
                'status' => 'todo',
                'priority' => 'medium',
                'checklist' => [
                    ['text' => 'Pas 1', 'done' => false],
                    ['text' => 'Pas 2', 'done' => true],
                ],
            ])
            ->assertRedirect(route('tasks.index'));

        $task = Task::query()->where('title', 'Task cu checklist')->first();

        $this->assertNotNull($task);
        $this->assertSame([
            ['text' => 'Pas 1', 'done' => false],
            ['text' => 'Pas 2', 'done' => true],
        ], $task->checklist);
    }

    public function test_task_materials_are_persisted_on_create(): void
    {
        $user = $this->createTenantUser('tasks.materials@santier.local');
        $project = $this->createProject($user, 'Proiect Consum');
        $material = Material::create([
            'tenant_id' => 1,
            'name' => 'Glet finisaj',
            'unit' => 'sac',
            'unit_price' => 35,
            'active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'project_id' => $project->id,
                'title' => 'Task consum materiale',
                'description' => 'Detalii',
                'status' => 'todo',
                'priority' => 'medium',
                'task_materials' => [
                    [
                        'material_id' => $material->id,
                        'quantity' => 3,
                        'unit_override' => 'sac',
                        'unit_price' => 37.5,
                    ],
                ],
            ])
            ->assertRedirect(route('tasks.index'));

        $task = Task::query()->where('title', 'Task consum materiale')->first();
        $this->assertNotNull($task);

        $this->assertDatabaseHas('task_material', [
            'task_id' => $task->id,
            'material_id' => $material->id,
        ]);
    }

    private function createTenantUser(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $this->seed(IamSeeder::class);

        return $user->fresh();
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

    private function createPhase(Project $project, string $name, string $status): ProjectPhase
    {
        return ProjectPhase::create([
            'project_id' => $project->id,
            'name' => $name,
            'type' => 'custom',
            'status' => $status,
            'progress_pct' => 0,
        ]);
    }
}
