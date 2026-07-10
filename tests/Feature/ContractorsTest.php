<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarded_user_can_create_contractor(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->post('/contractors', [
                'name' => 'Beton Expert SRL',
                'type' => 'subcontractor',
                'contact_name' => 'Ion Pop',
                'phone' => '0722111222',
                'email' => 'office@betonexpert.ro',
                'active' => true,
                'notes' => 'Disponibil pentru lucrari civile.',
            ]);

        $response->assertRedirect('/contractors');

        $this->assertDatabaseHas('contractors', [
            'tenant_id' => 1,
            'name' => 'Beton Expert SRL',
            'type' => 'subcontractor',
            'email' => 'office@betonexpert.ro',
        ]);
    }

    public function test_contractors_can_be_filtered_by_type(): void
    {
        $user = $this->createOnboardedUser();

        Contractor::create([
            'tenant_id' => 1,
            'name' => 'Echipa Interna Nord',
            'type' => 'internal_team',
            'active' => true,
        ]);

        Contractor::create([
            'tenant_id' => 1,
            'name' => 'Subcontractor Sud',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get('/contractors?type=subcontractor');

        $response->assertOk();
        $response->assertSee('Subcontractor Sud');
        $response->assertDontSee('Echipa Interna Nord');
    }

    public function test_phase_can_be_created_with_contractor_assignment(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Proiect test contractor',
            'status' => 'active',
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Montaj Fence Team',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/phases", [
            'name' => 'Montare panouri',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
            'contractor_id' => $contractor->id,
        ]);

        $response->assertRedirect();

        $phase = ProjectPhase::query()->where('project_id', $project->id)->first();

        $this->assertNotNull($phase);
        $this->assertSame($contractor->id, $phase->contractor_id);
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
