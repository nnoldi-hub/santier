<?php

namespace Tests\Feature;

use App\Models\CommercialTask;
use App\Models\PilotInvite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommercialDashboardInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_buckets_reflect_pending_commercial_work(): void
    {
        $admin = $this->createSuperadmin('admin@modulia.ro');

        $taskInvite = PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $admin->id,
            'company_name' => 'Task Today SRL',
            'contact_email' => 'task@invite.ro',
            'status' => 'contacted',
            'invited_at' => now(),
        ]);

        CommercialTask::create([
            'tenant_id' => 1,
            'pilot_invite_id' => $taskInvite->id,
            'assigned_to' => $admin->id,
            'created_by' => $admin->id,
            'title' => 'Follow-up comercial: Task Today SRL',
            'description' => 'Task de azi',
            'status' => 'todo',
            'priority' => 'medium',
            'due_at' => now(),
            'automated' => true,
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $admin->id,
            'company_name' => 'Overdue Follow Up SRL',
            'contact_email' => 'overdue@invite.ro',
            'status' => 'contacted',
            'invited_at' => now(),
            'follow_up_at' => now()->subDays(2),
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $admin->id,
            'company_name' => 'Stagnant Opportunity SRL',
            'contact_email' => 'stagnant@invite.ro',
            'status' => 'trial_started',
            'invited_at' => now(),
            'last_contacted_at' => now()->subDays(30),
        ]);

        PilotInvite::create([
            'tenant_id' => 1,
            'owner_id' => $admin->id,
            'company_name' => 'Pending Handoff SRL',
            'contact_email' => 'handoff@invite.ro',
            'status' => 'closed_won',
            'invited_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/commercial-dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CommercialDashboard')
                ->where('inbox.tasks_today.count', 1)
                ->where('inbox.follow_up_overdue.count', 1)
                ->where('inbox.stagnant_opportunities.count', 1)
                ->where('inbox.pending_handoffs.count', 1)
            );
    }

    private function createSuperadmin(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
