<?php

namespace App\Console\Commands;

use App\Models\Contractor;
use App\Models\Document;
use App\Models\Quote;
use Illuminate\Console\Command;

class FixAiToolsTenantMismatchCommand extends Command
{
    protected $signature = 'ai-tools:fix-tenant-mismatch {--dry-run : Afiseaza doar ce s-ar schimba, fara sa scrie in baza de date}';

    protected $description = 'Corecteaza tenant_id pe Quote/Document/Contractor create prin fluxurile AI Tools (deviz/factura), care erau salvate cu tenant_id=1 in loc de tenantul real al proiectului';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixedContractorIds = [];

        foreach ([Quote::class, Document::class] as $modelClass) {
            $label = $modelClass === Quote::class ? 'oferte' : 'documente';

            $rows = $modelClass::query()->with('project:id,tenant_id,name')->get();
            $mismatched = $rows->filter(fn ($row) => $row->project && (int) $row->project->tenant_id !== (int) $row->tenant_id);

            if ($mismatched->isEmpty()) {
                $this->info("Nicio nepotrivire de tenant gasita in {$label}.");
                continue;
            }

            $this->table(
                ['ID', 'Titlu', 'Proiect', 'tenant_id vechi', 'tenant_id corect'],
                $mismatched->map(fn ($row) => [
                    $row->id,
                    $row->title,
                    $row->project->name . " (#{$row->project_id})",
                    $row->tenant_id,
                    $row->project->tenant_id,
                ])
            );

            if ($dryRun) {
                $this->warn(count($mismatched) . " {$label} ar fi corectate (dry-run, nimic nu a fost scris).");
                continue;
            }

            foreach ($mismatched as $row) {
                if ($modelClass === Document::class && $row->contractor_id) {
                    $fixedContractorIds[$row->contractor_id] = (int) $row->project->tenant_id;
                }

                $row->update(['tenant_id' => $row->project->tenant_id]);
            }

            $this->info(count($mismatched) . " {$label} corectate.");
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        foreach ($fixedContractorIds as $contractorId => $correctTenantId) {
            $contractor = Contractor::find($contractorId);
            if (!$contractor || (int) $contractor->tenant_id === $correctTenantId) {
                continue;
            }

            $otherTenantsUsingContractor = Document::query()
                ->where('contractor_id', $contractorId)
                ->with('project:id,tenant_id')
                ->get()
                ->pluck('project.tenant_id')
                ->unique()
                ->filter(fn ($id) => (int) $id !== $correctTenantId);

            if ($otherTenantsUsingContractor->isNotEmpty()) {
                $this->warn("Contractor #{$contractorId} ({$contractor->name}) e folosit si de alt tenant - nu a fost corectat automat, verifica manual.");
                continue;
            }

            $contractor->update(['tenant_id' => $correctTenantId]);
            $this->info("Contractor #{$contractorId} ({$contractor->name}) corectat -> tenant_id {$correctTenantId}.");
        }

        return self::SUCCESS;
    }
}
