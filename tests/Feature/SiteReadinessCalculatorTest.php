<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\Project;
use App\Models\SiteCompliancePlan;
use App\Models\SiteContractorPlan;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteLogisticsPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use App\Models\User;
use App\Support\SiteReadinessCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SiteReadinessCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_domains_empty_yields_low_score_with_blockers(): void
    {
        $result = SiteReadinessCalculator::calculate(
            collect(),
            collect(),
            collect(),
            collect(),
            collect(),
            collect(),
            ['project_budget' => 0, 'difference' => 0]
        );

        $this->assertLessThan(50, $result['score']);
        $this->assertSame('Nepregatit', $result['label']);
        $this->assertNotEmpty($result['blockers']);
    }

    public function test_all_domains_complete_and_low_risk_yields_full_score(): void
    {
        $staffPlans = collect([new SiteStaffPlan(['risk_level' => 'low'])]);
        $contractorPlans = collect([new SiteContractorPlan(['contract_status' => 'signed', 'availability_status' => 'ok'])]);
        $materialPlans = collect([new SiteMaterialPlan(['risk_level' => 'low'])]);
        $equipmentPlan = new SiteEquipmentPlan(['risk_level' => 'low']);
        $equipmentPlan->setAttribute('reserved_elsewhere_count', 0);
        $equipmentPlans = collect([$equipmentPlan]);
        $logisticsPlans = collect([
            new SiteLogisticsPlan(['category' => 'access']),
            new SiteLogisticsPlan(['category' => 'storage']),
            new SiteLogisticsPlan(['category' => 'safety_zone']),
            new SiteLogisticsPlan(['category' => 'restriction']),
        ]);
        $compliancePlans = collect([new SiteCompliancePlan(['status' => 'valid'])]);
        $budgetSummary = ['project_budget' => 10000, 'difference' => 500];

        $result = SiteReadinessCalculator::calculate(
            $staffPlans,
            $contractorPlans,
            $materialPlans,
            $equipmentPlans,
            $logisticsPlans,
            $compliancePlans,
            $budgetSummary
        );

        $this->assertSame(100, $result['score']);
        $this->assertSame('Pregatit', $result['label']);
        $this->assertEmpty($result['blockers']);
    }

    public function test_budget_overrun_reduces_budget_domain_score(): void
    {
        $result = SiteReadinessCalculator::calculate(
            collect([new SiteStaffPlan(['risk_level' => 'low'])]),
            collect([new SiteContractorPlan(['contract_status' => 'signed', 'availability_status' => 'ok'])]),
            collect([new SiteMaterialPlan(['risk_level' => 'low'])]),
            collect(),
            collect(),
            collect(),
            ['project_budget' => 10000, 'difference' => -5000]
        );

        $this->assertSame(50, $result['domains']['budget']['score']);
        $this->assertNotEmpty($result['domains']['budget']['blockers']);
    }

    public function test_organizare_page_returns_readiness_score_in_payload(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-READY-001',
            'name' => 'Nisip',
            'unit' => 'mc',
            'unit_price' => 50,
            'active' => true,
        ]);

        SiteMaterialPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'material_id' => $material->id,
            'planned_quantity' => 5,
            'risk_level' => 'low',
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare");

        $response->assertInertia(function (Assert $page) {
            $page->has('readiness.score')
                ->has('readiness.label')
                ->has('readiness.domains')
                ->has('readiness.blockers');
        });
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Organizare SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Organizare',
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
        ]);
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
