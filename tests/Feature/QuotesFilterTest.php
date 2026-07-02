<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuotesFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_list_can_be_filtered_by_status_and_project(): void
    {
        $user = $this->createTenantUser('quotes.filter@santier.local');
        $projectA = $this->createProject($user, 'Proiect A');
        $projectB = $this->createProject($user, 'Proiect B');

        $this->createQuote($user, $projectA, 'Oferta A', 'draft', 1000);
        $selectedQuote = $this->createQuote($user, $projectB, 'Oferta B', 'sent', 2500);
        $this->createQuote($user, $projectB, 'Oferta C', 'accepted', 3200);

        $this->actingAs($user)
            ->get(route('quotes.index', [
                'status' => 'sent',
                'project_id' => $projectB->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Quotes/Index')
                ->has('quotes.data', 1)
                ->where('quotes.data.0.id', $selectedQuote->id)
                ->where('filters', function ($filters) use ($projectB): bool {
                    return $filters['status'] === 'sent'
                        && (int) $filters['project_id'] === (int) $projectB->id;
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

    private function createProject(User $user, string $name): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => $name . ' Client',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function createQuote(User $user, Project $project, string $title, string $status, float $totalGross): Quote
    {
        return Quote::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'version' => 1,
            'title' => $title,
            'status' => $status,
            'valid_until' => now()->addDays(14)->toDateString(),
            'discount_pct' => 0,
            'tva_pct' => 19,
            'notes' => 'Test quote',
            'meta' => [],
            'total_net' => $totalGross,
            'total_tva' => $totalGross * 0.19,
            'total_gross' => $totalGross * 1.19,
            'created_by' => $user->id,
        ]);
    }
}
