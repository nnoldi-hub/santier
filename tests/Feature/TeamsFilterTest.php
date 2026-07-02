<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamsFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_list_can_be_filtered_by_status_specialty_and_search(): void
    {
        $this->seed(IamSeeder::class);

        $user = $this->createTenantUser('teams.filter@santier.local');
        $leader = $this->createTenantUser('leader.filter@santier.local');

        $activeTeam = Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa Structuri',
            'specialty' => 'Structuri',
            'leader_id' => $leader->id,
            'active' => true,
            'notes' => 'Lucreaza pe proiecte mari',
        ]);

        Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa Finisaje',
            'specialty' => 'Finisaje',
            'leader_id' => $user->id,
            'active' => false,
            'notes' => 'Echipa de rezerva',
        ]);

        $this->actingAs($user)
            ->get(route('teams.index', [
                'status' => 'active',
                'specialty' => 'Structuri',
                'search' => 'mari',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teams/Index')
                ->has('teams.data', 1)
                ->where('teams.data.0.id', $activeTeam->id)
                ->where('filters', function ($filters) use ($activeTeam): bool {
                    return $filters['status'] === 'active'
                        && $filters['specialty'] === 'Structuri'
                        && $filters['search'] === 'mari';
                })
            );
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
}
