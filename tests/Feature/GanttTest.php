<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GanttTest extends TestCase
{
    use RefreshDatabase;

    public function test_gantt_response_includes_phase_buffer_days(): void
    {
        $user = $this->createOnboardedUser();

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Gantt',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Gantt',
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
        ]);

        ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'pending',
            'progress_pct' => 0,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'buffer_days' => 3,
        ]);

        $response = $this->actingAs($user)->get('/gantt?scope=single&project_id=' . $project->id);

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->where('phases.0.buffer_days', 3);
        });
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
