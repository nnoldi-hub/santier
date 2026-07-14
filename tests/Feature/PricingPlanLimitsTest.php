<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PricingPlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_cannot_create_more_than_one_project(): void
    {
        $user = $this->createOnboardedUser('free');
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client A',
            'type' => 'company',
            'active' => true,
        ]);

        $payload = [
            'name' => 'Proiect 1',
            'client_id' => $client->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
        ];

        $this->actingAs($user)->post('/projects', $payload)->assertRedirect();

        $payload['name'] = 'Proiect 2';
        $this->actingAs($user)
            ->from('/projects/create')
            ->post('/projects', $payload)
            ->assertRedirect('/projects/create');

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_free_plan_cannot_access_gantt(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)
            ->get('/gantt')
            ->assertRedirect('/dashboard');
    }

    public function test_starter_plan_can_access_gantt_and_csv_exports(): void
    {
        $user = $this->createOnboardedUser('starter');

        $this->actingAs($user)->get('/gantt')->assertStatus(200);
        $this->actingAs($user)->get('/exports/projects')->assertStatus(200);
    }

    public function test_starter_plan_cannot_access_enterprise_exports(): void
    {
        $user = $this->createOnboardedUser('starter');

        $this->actingAs($user)
            ->get('/exports/workbook')
            ->assertRedirect('/dashboard');
    }

    public function test_pro_plan_can_access_enterprise_exports(): void
    {
        $user = $this->createOnboardedUser('pro');

        $this->actingAs($user)->get('/exports/workbook')->assertStatus(200);
    }

    public function test_free_plan_cannot_invite_any_new_users(): void
    {
        $owner = $this->createOnboardedUser('free');
        $role = Role::where('name', 'data_entry')->firstOrFail();

        $response = $this->actingAs($owner)
            ->from('/account/users')
            ->post('/account/users/invite', [
                'email' => 'noumembru@test.ro',
                'role_id' => $role->id,
            ]);

        $response->assertRedirect('/account/users');
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'noumembru@test.ro']);
        $this->assertDatabaseCount('tenant_users', 1);
    }

    public function test_starter_plan_allows_invites_up_to_the_seat_limit(): void
    {
        $owner = $this->createOnboardedUser('starter');
        $role = Role::where('name', 'data_entry')->firstOrFail();

        $this->actingAs($owner)->post('/account/users/invite', [
            'email' => 'membru1@test.ro',
            'role_id' => $role->id,
        ])->assertSessionMissing('error');

        $this->actingAs($owner)->post('/account/users/invite', [
            'email' => 'membru2@test.ro',
            'role_id' => $role->id,
        ])->assertSessionMissing('error');

        $this->assertDatabaseCount('tenant_users', 3);

        $response = $this->actingAs($owner)
            ->from('/account/users')
            ->post('/account/users/invite', [
                'email' => 'membru3@test.ro',
                'role_id' => $role->id,
            ]);

        $response->assertRedirect('/account/users');
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'membru3@test.ro']);
        $this->assertDatabaseCount('tenant_users', 3);
    }

    public function test_reinviting_an_existing_member_is_not_blocked_by_the_limit(): void
    {
        $owner = $this->createOnboardedUser('free');
        $existingMember = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'email' => 'membruexistent@test.ro',
        ]);
        TenantUser::create([
            'tenant_id' => 1,
            'user_id' => $existingMember->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $newRole = Role::where('name', 'quote_specialist')->firstOrFail();

        $response = $this->actingAs($owner)->post('/account/users/invite', [
            'email' => 'membruexistent@test.ro',
            'role_id' => $newRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');
        $response->assertSessionHas('success');
        $this->assertTrue($existingMember->fresh()->hasRole('quote_specialist'));
    }

    private function createOnboardedUser(string $plan): User
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => $plan,
        ]);

        $this->seed(IamSeeder::class);
        Tenant::find(1)?->update(['billing_plan' => $plan]);

        return $user->fresh();
    }
}
