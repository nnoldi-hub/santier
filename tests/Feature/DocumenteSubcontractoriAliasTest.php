<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumenteSubcontractoriAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_documente_subcontractori_alias_filters_subcontractors(): void
    {
        $user = $this->createOnboardedUser();

        Contractor::create([
            'tenant_id' => 1,
            'name' => 'Subcontractor Alpha',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Ion',
            'active' => true,
        ]);

        Contractor::create([
            'tenant_id' => 1,
            'name' => 'Furnizor Beta',
            'type' => Contractor::TYPE_MATERIALS_SUPPLIER,
            'contact_name' => 'Maria',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get('/documente-subcontractori');
        $expectedType = 'subcontractor';
        $expectedName = 'Subcontractor Alpha';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedType, $expectedName): void {
            $page->component('Contractors/Index')
            ->where('filters.type', $expectedType)
            ->where('contractors.data.0.name', $expectedName)
                ->has('contractors.data', 1);
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
