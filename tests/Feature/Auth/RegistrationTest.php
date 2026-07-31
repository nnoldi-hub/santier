<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0722111222',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('onboarding.show', absolute: false));
    }

    public function test_registration_requires_a_phone_number(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'nophone@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'nophone@example.com']);
    }

    public function test_registration_saves_phone_and_creates_a_pilot_invite(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'leadtest@example.com',
            'phone' => '0733444555',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'leadtest@example.com')->firstOrFail();
        $this->assertSame('0733444555', $user->phone);

        $this->assertDatabaseHas('pilot_invites', [
            'contact_email' => 'leadtest@example.com',
            'contact_phone' => '0733444555',
            'converted_tenant_id' => $user->tenant_id,
            'status' => 'trial_started',
            'commercial_stage' => 'trial',
        ]);
    }

    public function test_registration_updates_an_existing_pilot_invite_instead_of_duplicating_it(): void
    {
        $existingInvite = \App\Models\PilotInvite::create([
            'tenant_id' => 1,
            'company_name' => 'Firma Existenta SRL',
            'contact_email' => 'PrecedentLead@Example.com',
            'status' => 'invited',
            'commercial_stage' => 'prospecting',
            'invited_at' => now()->subDays(3),
        ]);

        $this->post('/register', [
            'name' => 'Precedent Lead',
            'email' => 'precedentlead@example.com',
            'phone' => '0744555666',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'precedentlead@example.com')->firstOrFail();

        $this->assertDatabaseCount('pilot_invites', 1);
        $existingInvite->refresh();
        $this->assertSame('trial_started', $existingInvite->status);
        $this->assertSame($user->tenant_id, $existingInvite->converted_tenant_id);
        $this->assertSame('0744555666', $existingInvite->contact_phone);
    }

    public function test_new_users_are_assigned_tenant_admin_role(): void
    {
        $this->seed(IamSeeder::class);

        $this->post('/register', [
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'phone' => '0755666777',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'owner@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('tenant_admin'));
        $this->assertTrue($user->can('documents.view'));
    }
}
