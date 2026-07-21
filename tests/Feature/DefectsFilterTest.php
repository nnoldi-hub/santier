<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\DefectPhoto;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DefectsFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_defect_list_can_be_filtered_by_status_priority_and_project(): void
    {
        $this->seed(IamSeeder::class);

        $user = $this->createTenantUser('defects.filter@santier.local');
        $projectA = $this->createProject($user, 'Proiect A');
        $projectB = $this->createProject($user, 'Proiect B');

        $this->createDefect($user, $projectA, 'Defect deschis', 'open', 'high');
        $selectedDefect = $this->createDefect($user, $projectB, 'Defect blocat', 'in_progress', 'medium', 'Filtrare buna');
        $this->createDefect($user, $projectB, 'Defect rezolvat', 'resolved', 'low');

        $this->actingAs($user)
            ->get(route('defects.index', [
                'status' => 'in_progress',
                'priority' => 'medium',
                'project_id' => $projectB->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Defects/Index')
                ->has('defects.data', 1)
                ->where('defects.data.0.id', $selectedDefect->id)
                ->where('filters', function ($filters) use ($projectB): bool {
                    return $filters['status'] === 'in_progress'
                        && $filters['priority'] === 'medium'
                        && (int) $filters['project_id'] === (int) $projectB->id;
                })
            );
    }

    public function test_defect_status_update_sets_resolved_timestamp(): void
    {
        $this->seed(IamSeeder::class);

        $user = $this->createTenantUser('defects.status@santier.local');
        $project = $this->createProject($user, 'Proiect Status');
        $defect = $this->createDefect($user, $project, 'Defect de status', 'open', 'high');
        DefectPhoto::create([
            'tenant_id' => 1,
            'defect_id' => $defect->id,
            'path' => 'defects/photos/existing.jpg',
            'name' => 'existing.jpg',
        ]);

        $this->actingAs($user)
            ->patch(route('defects.status', $defect), [
                'status' => 'resolved',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('defects', [
            'id' => $defect->id,
            'status' => 'resolved',
        ]);
        $this->assertNotNull($defect->fresh()->resolved_at);
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

    private function createDefect(User $user, Project $project, string $title, string $status, string $priority, ?string $description = null): Defect
    {
        return Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'reported_by' => $user->id,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
    }
}
