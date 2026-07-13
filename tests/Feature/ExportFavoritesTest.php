<?php

namespace Tests\Feature;

use App\Models\ReportFavorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportFavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_report_favorite(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->from('/exports')
            ->post('/exports/favorites', [
                'label' => 'Raport PM saptamanal',
                'export_type' => 'projects',
                'format' => 'xlsx',
                'filters' => ['status' => ['active']],
            ]);

        $response->assertRedirect('/exports');

        $this->assertDatabaseHas('report_favorites', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'label' => 'Raport PM saptamanal',
            'export_type' => 'projects',
            'format' => 'xlsx',
        ]);
    }

    public function test_favorite_requires_a_valid_export_type(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->post('/exports/favorites', [
                'label' => 'Invalid',
                'export_type' => 'not-a-real-type',
                'format' => 'xlsx',
            ]);

        $response->assertSessionHasErrors('export_type');
        $this->assertDatabaseCount('report_favorites', 0);
    }

    public function test_favorite_rejects_csv_format_for_resource_comparison(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->post('/exports/favorites', [
                'label' => 'Comparatie resurse CSV',
                'export_type' => 'resource-comparison',
                'format' => 'csv',
            ]);

        $response->assertSessionHasErrors('format');
        $this->assertDatabaseCount('report_favorites', 0);
    }

    public function test_favorite_rejects_csv_format_for_material_timeline_and_equipment_consumption(): void
    {
        $user = $this->createOnboardedUser();

        foreach (['material-timeline', 'equipment-consumption'] as $exportType) {
            $response = $this->actingAs($user)
                ->post('/exports/favorites', [
                    'label' => 'CSV nepermis',
                    'export_type' => $exportType,
                    'format' => 'csv',
                ]);

            $response->assertSessionHasErrors('format');
        }

        $this->assertDatabaseCount('report_favorites', 0);
    }

    public function test_user_cannot_delete_another_users_favorite(): void
    {
        $owner = $this->createOnboardedUser();
        $intruder = $this->createOnboardedUser();

        $favorite = ReportFavorite::create([
            'tenant_id' => 1,
            'user_id' => $owner->id,
            'label' => 'Raport privat',
            'export_type' => 'projects',
            'format' => 'xlsx',
            'filters' => [],
        ]);

        $response = $this->actingAs($intruder)->delete("/exports/favorites/{$favorite->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('report_favorites', ['id' => $favorite->id]);
    }

    public function test_owner_can_delete_own_favorite(): void
    {
        $user = $this->createOnboardedUser();

        $favorite = ReportFavorite::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'label' => 'Raport de sters',
            'export_type' => 'projects',
            'format' => 'xlsx',
            'filters' => [],
        ]);

        $response = $this->actingAs($user)->delete("/exports/favorites/{$favorite->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('report_favorites', ['id' => $favorite->id]);
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
