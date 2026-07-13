<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Defect;
use App\Models\Material;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportPreviewChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_preview_returns_status_and_priority_charts(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Task todo high',
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now()->addDay(),
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Task todo medium',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => now()->addDay(),
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Task done high',
            'status' => 'done',
            'priority' => 'high',
            'deadline' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=tasks');

        $response->assertOk();
        $charts = $response->json('charts');

        $this->assertCount(2, $charts);

        $statusChart = collect($charts)->firstWhere('key', 'tasks_status');
        $this->assertNotNull($statusChart);
        $this->assertSame(['todo', 'done'], $statusChart['labels']);
        $this->assertSame([2, 1], $statusChart['series']);

        $priorityChart = collect($charts)->firstWhere('key', 'tasks_priority');
        $this->assertNotNull($priorityChart);
        $this->assertSame(['high', 'medium'], $priorityChart['labels']);
        $this->assertSame([2, 1], $priorityChart['series']);
    }

    public function test_materials_preview_returns_boolean_active_chart(): void
    {
        $user = $this->createOnboardedUser();

        Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-ACT',
            'name' => 'Material activ',
            'category' => 'Constructii',
            'unit' => 'buc',
            'unit_price' => 10,
            'active' => true,
        ]);

        Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-INACT',
            'name' => 'Material inactiv',
            'category' => 'Constructii',
            'unit' => 'buc',
            'unit_price' => 5,
            'active' => false,
        ]);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=materials');

        $response->assertOk();
        $charts = $response->json('charts');

        $this->assertCount(1, $charts);
        $this->assertEqualsCanonicalizing(['Activ', 'Inactiv'], $charts[0]['labels']);
    }

    public function test_defects_preview_returns_status_and_priority_charts(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'reported_by' => $user->id,
            'assigned_to' => $user->id,
            'title' => 'Defect open high',
            'status' => 'open',
            'priority' => 'high',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=defects');

        $response->assertOk();
        $charts = $response->json('charts');

        $this->assertCount(2, $charts);
    }

    public function test_costs_preview_returns_no_charts(): void
    {
        $user = $this->createOnboardedUser();
        $this->createProject($user);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=costs');

        $response->assertOk();
        $this->assertSame([], $response->json('charts'));
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Demo Charts SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Demo Charts',
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
