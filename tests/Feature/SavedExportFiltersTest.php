<?php

namespace Tests\Feature;

use App\Models\SavedExportFilter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SavedExportFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_filter_preset(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->from('/exports')
            ->post('/exports/saved-filters', [
                'name' => 'Proiecte active - luna curenta',
                'filters' => ['status' => ['active'], 'quick_range' => 'this_year'],
            ]);

        $response->assertRedirect('/exports');

        $this->assertDatabaseHas('saved_export_filters', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'name' => 'Proiecte active - luna curenta',
        ]);
    }

    public function test_index_only_lists_saved_filters_for_current_user(): void
    {
        $user = $this->createOnboardedUser();
        $otherUser = $this->createOnboardedUser();

        SavedExportFilter::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'name' => 'Filtrul meu',
            'filters' => [],
        ]);

        SavedExportFilter::create([
            'tenant_id' => 1,
            'user_id' => $otherUser->id,
            'name' => 'Filtrul altcuiva',
            'filters' => [],
        ]);

        $this->actingAs($user)
            ->get('/exports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Exports/Index')
                ->has('savedFilters', 1)
                ->where('savedFilters.0.name', 'Filtrul meu')
            );
    }

    public function test_user_cannot_delete_another_users_saved_filter(): void
    {
        $owner = $this->createOnboardedUser();
        $intruder = $this->createOnboardedUser();

        $saved = SavedExportFilter::create([
            'tenant_id' => 1,
            'user_id' => $owner->id,
            'name' => 'Filtru privat',
            'filters' => [],
        ]);

        $response = $this->actingAs($intruder)->delete("/exports/saved-filters/{$saved->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('saved_export_filters', ['id' => $saved->id]);
    }

    public function test_owner_can_delete_own_saved_filter(): void
    {
        $user = $this->createOnboardedUser();

        $saved = SavedExportFilter::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'name' => 'Filtru de sters',
            'filters' => [],
        ]);

        $response = $this->actingAs($user)->delete("/exports/saved-filters/{$saved->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('saved_export_filters', ['id' => $saved->id]);
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
