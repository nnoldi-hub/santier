<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_supplier(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->from('/suppliers')
            ->post('/suppliers', [
                'name' => 'Dedeman SRL',
                'contact_name' => 'Ana Popescu',
                'phone' => '0722123456',
                'email' => 'ana@dedeman.ro',
                'active' => true,
            ]);

        $response->assertRedirect('/suppliers');

        $this->assertDatabaseHas('suppliers', [
            'tenant_id' => 1,
            'name' => 'Dedeman SRL',
            'contact_name' => 'Ana Popescu',
            'phone' => '0722123456',
            'email' => 'ana@dedeman.ro',
        ]);
    }

    public function test_name_is_required(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->post('/suppliers', [
                'active' => true,
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_user_can_update_a_supplier(): void
    {
        $user = $this->createOnboardedUser();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Furnizor Vechi', 'active' => true]);

        $response = $this->actingAs($user)
            ->patch("/suppliers/{$supplier->id}", [
                'name' => 'Furnizor Nou',
                'active' => true,
            ]);

        $response->assertRedirect('/suppliers');
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Furnizor Nou']);
    }

    public function test_user_can_delete_a_supplier(): void
    {
        $user = $this->createOnboardedUser();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Furnizor Test', 'active' => true]);

        $response = $this->actingAs($user)->delete("/suppliers/{$supplier->id}");

        $response->assertRedirect('/suppliers');
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_user_cannot_manage_suppliers_for_other_tenant(): void
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

        $otherSupplier = Supplier::create(['tenant_id' => 2, 'name' => 'Furnizor Intrus', 'active' => true]);

        $this->actingAs($user)->patch("/suppliers/{$otherSupplier->id}", [
            'name' => 'Preluat',
            'active' => true,
        ])->assertForbidden();

        $this->actingAs($user)->delete("/suppliers/{$otherSupplier->id}")->assertForbidden();

        $this->assertDatabaseHas('suppliers', ['id' => $otherSupplier->id, 'name' => 'Furnizor Intrus']);
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
