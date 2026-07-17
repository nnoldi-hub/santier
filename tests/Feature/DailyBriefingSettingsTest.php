<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectDailyBriefingSetting;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyBriefingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_daily_briefing_settings(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/memento")
            ->patch("/projects/{$project->id}/memento/setari", [
                'enabled' => true,
                'send_time' => '07:30',
                'recipient_user_ids' => [$user->id],
                'detail_level' => 'complet',
                'channels' => ['email' => true, 'in_app' => true],
            ]);

        $response->assertRedirect("/projects/{$project->id}/memento");
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_daily_briefing_settings', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'enabled' => 1,
            'detail_level' => 'complet',
        ]);
    }

    public function test_send_time_and_detail_level_are_validated(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->patch("/projects/{$project->id}/memento/setari", [
                'enabled' => true,
                'send_time' => 'not-a-time',
                'detail_level' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['send_time', 'detail_level']);
        $this->assertDatabaseCount('project_daily_briefing_settings', 0);
    }

    public function test_recipient_user_ids_must_belong_to_tenant(): void
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
        $otherUser = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2]);

        $response = $this->actingAs($user)
            ->patch("/projects/{$project->id}/memento/setari", [
                'enabled' => true,
                'send_time' => '07:30',
                'recipient_user_ids' => [$otherUser->id],
                'detail_level' => 'complet',
            ]);

        $response->assertSessionHasErrors('recipient_user_ids.0');
        $this->assertDatabaseCount('project_daily_briefing_settings', 0);
    }

    public function test_user_cannot_update_settings_for_other_tenant_project(): void
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

        $response = $this->actingAs($user)
            ->patch("/projects/{$otherProject->id}/memento/setari", [
                'enabled' => true,
                'send_time' => '07:30',
                'detail_level' => 'complet',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('project_daily_briefing_settings', 0);
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
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        TenantUser::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user;
    }
}
