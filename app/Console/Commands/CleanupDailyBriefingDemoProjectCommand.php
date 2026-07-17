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
use Illuminate\Console\Command;

class CleanupDailyBriefingDemoProjectCommand extends Command
{
    protected $signature = 'briefing:cleanup-demo {project : ID-ul proiectului de test creat de briefing:seed-demo}';

    protected $description = 'Sterge complet un proiect de test creat de briefing:seed-demo, inclusiv catalogul asociat (client, echipa, subcontractor, material, utilaj)';

    public function handle(): int
    {
        $projectId = (int) $this->argument('project');
        // withTrashed(): the project may already have been soft-deleted through
        // the app's normal "Sterge proiect" action before this command runs.
        $project = Project::withTrashed()->find($projectId);

        if (!$project) {
            $this->error("Proiectul #{$projectId} nu exista.");

            return self::FAILURE;
        }

        if (!str_starts_with((string) $project->name, 'Proiect Test Memento')) {
            $this->error('Aceasta comanda sterge doar proiecte create de briefing:seed-demo (nume incepand cu "Proiect Test Memento"). Nu continui.');

            return self::FAILURE;
        }

        $phaseIds = $project->phases()->pluck('id');
        $teamIds = PhaseTeamAssignment::whereIn('phase_id', $phaseIds)->pluck('team_id')->unique();
        $contractorIds = ProjectPhase::whereIn('id', $phaseIds)->whereNotNull('contractor_id')->pluck('contractor_id')->unique();
        $materialIds = ResourceOrder::where('project_id', $project->id)->whereNotNull('material_id')->pluck('material_id')->unique();
        $equipmentIds = StageEquipment::whereIn('stage_id', $phaseIds)->pluck('equipment_id')->unique();
        $clientId = $project->client_id;

        // forceDelete() where the model uses SoftDeletes (Task, StageTask,
        // ResourceOrder, Team, Equipment, Client, Contractor, Project) - a plain
        // delete() on those would only set deleted_at and leave the rows behind,
        // which defeats the point of a test-data cleanup command.
        StageTask::whereIn('stage_id', $phaseIds)->forceDelete();
        StageEquipment::whereIn('stage_id', $phaseIds)->delete();
        ResourceOrder::where('project_id', $project->id)->forceDelete();
        SiteCompliancePlan::where('project_id', $project->id)->delete();
        Task::where('project_id', $project->id)->forceDelete();
        PhaseTeamAssignment::whereIn('phase_id', $phaseIds)->delete();
        ProjectDailyBriefingSetting::where('project_id', $project->id)->delete();
        ProjectPhase::where('project_id', $project->id)->delete();
        $project->forceDelete();

        Client::withTrashed()->whereKey($clientId)->forceDelete();
        Team::withTrashed()->whereIn('id', $teamIds)->forceDelete();
        Contractor::withTrashed()->whereIn('id', $contractorIds)->forceDelete();
        Material::withTrashed()->whereIn('id', $materialIds)->forceDelete();
        Equipment::withTrashed()->whereIn('id', $equipmentIds)->forceDelete();

        $this->info("Proiectul de test #{$projectId} si datele asociate (client, echipa, subcontractor, material, utilaj) au fost sterse.");

        return self::SUCCESS;
    }
}
