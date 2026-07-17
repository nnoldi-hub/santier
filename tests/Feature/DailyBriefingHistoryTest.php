<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectDailyBriefingLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyBriefingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_includes_history_for_the_project(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        ProjectDailyBriefingLog::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'briefing_date' => now()->toDateString(),
            'sent_at' => now(),
            'risk_level' => 'red',
            'blockers_count' => 3,
            'recipients_count' => 2,
            'channels' => ['email' => true, 'in_app' => true, 'whatsapp' => false],
            'snapshot' => ['risk_level' => 'red', 'summary' => 'Azi ai 3 blocaj(e).'],
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/memento");

        $response->assertInertia(function (Assert $page) {
            $page->has('history', 1)
                ->where('history.0.risk_level', 'red')
                ->where('history.0.blockers_count', 3)
                ->has('history.0.snapshot');
        });
    }

    public function test_history_is_isolated_per_tenant(): void
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
        $project = $this->createProject($user);

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

        ProjectDailyBriefingLog::create([
            'tenant_id' => 2,
            'project_id' => $otherProject->id,
            'briefing_date' => now()->toDateString(),
            'sent_at' => now(),
            'risk_level' => 'red',
            'blockers_count' => 1,
            'recipients_count' => 1,
            'channels' => [],
            'snapshot' => [],
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/memento");

        $response->assertInertia(fn (Assert $page) => $page->has('history', 0));
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Memento SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Memento',
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
