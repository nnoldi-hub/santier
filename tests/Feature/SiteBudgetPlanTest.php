<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\Project;
use App\Models\SiteBudgetPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use App\Models\SiteStaffTimeEntry;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SiteBudgetPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_budget_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/budget-plans", [
                'category' => 'labor',
                'description' => 'Manopera echipa structura',
                'estimated_cost' => 12000,
            ]);

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseHas('site_budget_plans', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'category' => 'labor',
            'description' => 'Manopera echipa structura',
            'estimated_cost' => 12000,
        ]);
    }

    public function test_category_description_and_estimated_cost_are_required(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/budget-plans", []);

        $response->assertSessionHasErrors(['category', 'description', 'estimated_cost']);
        $this->assertDatabaseCount('site_budget_plans', 0);
    }

    public function test_user_can_delete_a_budget_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $plan = SiteBudgetPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'category' => 'contingency',
            'description' => 'Rezerva neprevazute',
            'estimated_cost' => 5000,
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->id}/organizare/budget-plans/{$plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_budget_plans', ['id' => $plan->id]);
    }

    public function test_user_cannot_manage_budget_plans_for_other_tenant_project(): void
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
            ->post("/projects/{$otherProject->id}/organizare/budget-plans", [
                'category' => 'other',
                'description' => 'Linie intrusa',
                'estimated_cost' => 100,
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('site_budget_plans', 0);
    }

    public function test_budget_summary_aggregates_materials_equipment_and_manual_costs(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user, 20000);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-BUG-001',
            'name' => 'Ciment',
            'unit' => 'sac',
            'unit_price' => 25,
            'active' => true,
        ]);

        SiteMaterialPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'material_id' => $material->id,
            'planned_quantity' => 10,
            'unit_price' => $material->unit_price,
            'risk_level' => 'low',
        ]);

        SiteBudgetPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'category' => 'contingency',
            'description' => 'Rezerva',
            'estimated_cost' => 3000,
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare");

        $response->assertInertia(function (Assert $page) {
            $page->where('budgetSummary.labor_cost', 0)
                ->where('budgetSummary.materials_cost', 250)
                ->where('budgetSummary.equipment_cost', 0)
                ->where('budgetSummary.manual_cost', 3000)
                ->where('budgetSummary.total_estimated', 3250)
                ->where('budgetSummary.project_budget', 20000)
                ->where('budgetSummary.difference', 16750);
        });

        $material->update(['unit_price' => 999]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare");

        $response->assertInertia(function (Assert $page) {
            $page->where('budgetSummary.materials_cost', 250);
        });
    }

    public function test_budget_summary_includes_automatic_labor_cost_and_excludes_manual_labor_lines(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user, 20000);

        SiteStaffPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'specialty' => 'Zidar',
            'planned_headcount' => 2,
            'hourly_rate' => 50,
            'planned_start' => '2026-01-01',
            'planned_end' => '2026-01-03',
            'risk_level' => 'medium',
        ]);

        SiteBudgetPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'category' => 'labor',
            'description' => 'Manopera introdusa manual (nu ar trebui numarata)',
            'estimated_cost' => 9999,
        ]);

        SiteBudgetPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'category' => 'contingency',
            'description' => 'Rezerva',
            'estimated_cost' => 500,
        ]);

        // 3 zile * 8h * 2 oameni * 50 RON/h = 2400
        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare");

        $response->assertInertia(function (Assert $page) {
            $page->where('budgetSummary.labor_cost', 2400)
                ->where('budgetSummary.manual_cost', 500)
                ->where('budgetSummary.total_estimated', 2900);
        });
    }

    public function test_budget_summary_reports_actual_labor_cost_without_including_it_in_total(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user, 20000);

        $plan = SiteStaffPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'specialty' => 'Zidar',
            'planned_headcount' => 2,
            'hourly_rate' => 50,
            'planned_start' => '2026-01-01',
            'planned_end' => '2026-01-03',
            'risk_level' => 'medium',
        ]);

        SiteStaffTimeEntry::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'staff_plan_id' => $plan->id,
            'entry_date' => '2026-01-01',
            'hours_worked' => 10,
        ]);

        // estimat: 3 zile * 8h * 2 oameni * 50 RON/h = 2400; real: 10h * 50 RON/h = 500
        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare");

        $response->assertInertia(function (Assert $page) {
            $page->where('budgetSummary.labor_cost', 2400)
                ->where('budgetSummary.labor_cost_actual', 500)
                ->where('budgetSummary.total_estimated', 2400);
        });
    }

    private function createProject(User $user, float $totalBudget = 50000): Project
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
            'total_budget' => $totalBudget,
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
