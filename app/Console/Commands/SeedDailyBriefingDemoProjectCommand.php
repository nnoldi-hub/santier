<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectDailyBriefingSetting;
use App\Models\ProjectPhase;
use App\Models\ResourceOrder;
use App\Models\SiteCompliancePlan;
use App\Models\StageEquipment;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;

class SeedDailyBriefingDemoProjectCommand extends Command
{
    protected $signature = 'briefing:seed-demo {email? : Emailul contului care va primi mementoul (implicit primul cont superadmin)}';

    protected $description = 'Creeaza un proiect de test cu date programate azi, pentru verificarea mementoului zilnic';

    public function handle(): int
    {
        $email = $this->argument('email');
        $recipient = $email
            ? User::where('email', $email)->first()
            : User::where('is_superadmin', true)->first();

        if (!$recipient) {
            $this->error('Nu am gasit niciun utilizator' . ($email ? " cu emailul {$email}" : ' superadmin') . '.');

            return self::FAILURE;
        }

        $tenantId = (int) $recipient->tenant_id;

        $client = Client::create([
            'tenant_id' => $tenantId,
            'name' => 'Client Test Memento SRL',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => $tenantId,
            'client_id' => $client->id,
            'created_by' => $recipient->id,
            'name' => 'Proiect Test Memento ' . now()->format('d.m H:i'),
            'status' => 'active',
            'total_budget' => 100000,
            'start_date' => now()->subDays(5)->toDateString(),
        ]);

        $phaseStructura = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'in_progress',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $team = Team::create(['tenant_id' => $tenantId, 'name' => 'Echipa Fier-Beton Test', 'active' => true]);
        PhaseTeamAssignment::create([
            'phase_id' => $phaseStructura->id,
            'team_id' => $team->id,
            'workers_needed' => 6,
            'workers_assigned' => 3,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'notes' => 'Generat automat pentru test memento.',
        ]);

        $contractor = Contractor::create(['tenant_id' => $tenantId, 'name' => 'Instalatii Test SRL', 'type' => 'subcontractor', 'active' => true]);
        $phaseInstalatii = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Instalatii electrice',
            'type' => 'instalatii_brute',
            'order' => 2,
            'status' => 'blocked',
            'contractor_id' => $contractor->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $material = Material::create(['tenant_id' => $tenantId, 'name' => 'Beton C25/30 Test', 'unit' => 'mc']);
        ResourceOrder::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'phase_id' => $phaseStructura->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Holcim Test',
            'ordered_quantity' => 12,
            'ordered_unit' => 'mc',
            'delivery_date' => now()->toDateString(),
            'status' => 'blocked_payment',
        ]);

        $equipment = Equipment::create(['tenant_id' => $tenantId, 'name' => 'Pompa beton Test', 'type' => 'custom', 'availability_status' => 'reserved', 'active' => true]);
        StageEquipment::create([
            'stage_id' => $phaseStructura->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => now()->toDateString(),
            'usage_end' => now()->toDateString(),
        ]);

        SiteCompliancePlan::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'item_type' => 'autorizatie',
            'title' => 'Autorizatie constructie (test)',
            'status' => 'expired',
            'due_date' => now()->toDateString(),
        ]);

        Task::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'created_by' => $recipient->id,
            'title' => 'Verifica santierul (test)',
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now(),
        ]);

        StageTask::create([
            'stage_id' => $phaseInstalatii->id,
            'title' => 'Racordare tablou electric (test)',
            'status' => 'blocked',
            'deadline' => now(),
        ]);

        $sendTime = now()->addMinutes(2)->format('H:i');

        ProjectDailyBriefingSetting::updateOrCreate(
            ['project_id' => $project->id],
            [
                'tenant_id' => $tenantId,
                'enabled' => true,
                'send_time' => $sendTime,
                'recipient_user_ids' => [$recipient->id],
                'detail_level' => 'complet',
                'channels' => ['email' => true, 'in_app' => true, 'whatsapp' => false],
            ]
        );

        $this->info("Proiect de test creat: {$project->name} (ID {$project->id})");
        $this->info("Memento zilnic activat pentru {$recipient->email}, ora trimiterii: {$sendTime}");
        $this->info("Vezi manual: /projects/{$project->id}/memento");
        $this->info('Pentru trimitere imediata (fara sa astepti ora setata): php artisan briefing:send-daily');

        return self::SUCCESS;
    }
}
