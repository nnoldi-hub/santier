<?php

namespace App\Support;

use App\Models\SiteBudgetPlan;
use App\Models\SiteCompliancePlan;
use App\Models\SiteContractorPlan;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteLogisticsPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use Illuminate\Support\Collection;

class SitePlanningExporter
{
    public static function buildSections(array $data): array
    {
        return [
            self::staffSection($data['staffPlans']),
            self::contractorsSection($data['contractorPlans']),
            self::materialsSection($data['materialPlans']),
            self::equipmentSection($data['equipmentPlans']),
            self::logisticsSection($data['logisticsPlans']),
            self::complianceSection($data['compliancePlans']),
            self::budgetLinesSection($data['budgetPlans']),
            self::budgetSummarySection($data['budgetSummary']),
            self::readinessSection($data['readiness']),
            self::aiSuggestionsSection($data['aiSuggestions']),
        ];
    }

    private static function staffSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Specialitate', 'Necesar', 'Responsabil', 'Inceput', 'Sfarsit', 'Ore estimate', 'Cost estimat', 'Suprapuneri echipa', 'Risc', 'Note'];

        $rows = $plans->map(fn (SiteStaffPlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Specialitate' => $plan->specialty,
            'Necesar' => $plan->planned_headcount,
            'Responsabil' => $plan->team?->name ?? $plan->contractor?->name ?? '-',
            'Inceput' => self::formatDate($plan->planned_start),
            'Sfarsit' => self::formatDate($plan->planned_end),
            'Ore estimate' => $plan->estimated_hours ?? '-',
            'Cost estimat' => number_format((float) ($plan->estimated_cost ?? 0), 2) . ' lei',
            'Suprapuneri echipa' => $plan->team_overlap_count ?? 0,
            'Risc' => SiteStaffPlan::$riskLabels[$plan->risk_level] ?? $plan->risk_level,
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Echipe & specialitati', 'headings' => $headings, 'rows' => $rows];
    }

    private static function contractorsSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Subcontractor', 'Contract', 'Disponibilitate', 'Proiecte paralele', 'Inceput', 'Sfarsit', 'Note'];

        $rows = $plans->map(fn (SiteContractorPlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Subcontractor' => $plan->contractor?->name ?? '-',
            'Contract' => SiteContractorPlan::$contractStatusLabels[$plan->contract_status] ?? $plan->contract_status,
            'Disponibilitate' => SiteContractorPlan::$availabilityLabels[$plan->availability_status] ?? $plan->availability_status,
            'Proiecte paralele' => $plan->parallel_projects_count ?? 0,
            'Inceput' => self::formatDate($plan->planned_start),
            'Sfarsit' => self::formatDate($plan->planned_end),
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Subcontractori', 'headings' => $headings, 'rows' => $rows];
    }

    private static function materialsSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Material', 'Cantitate', 'Pret unitar', 'Cost estimat', 'Furnizor', 'Lead-time (zile)', 'Comanda planificata', 'Livrare planificata', 'Risc', 'Note'];

        $rows = $plans->map(fn (SiteMaterialPlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Material' => $plan->material?->name ?? '-',
            'Cantitate' => $plan->planned_quantity . ' ' . ($plan->material?->unit ?? ''),
            'Pret unitar' => number_format((float) ($plan->unit_price ?? 0), 2) . ' lei',
            'Cost estimat' => number_format((float) ($plan->estimated_cost ?? 0), 2) . ' lei',
            'Furnizor' => $plan->supplier_name ?? '-',
            'Lead-time (zile)' => $plan->lead_time_days ?? '-',
            'Comanda planificata' => self::formatDate($plan->planned_order_date),
            'Livrare planificata' => self::formatDate($plan->planned_delivery_date),
            'Risc' => SiteMaterialPlan::$riskLabels[$plan->risk_level] ?? $plan->risk_level,
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Materiale', 'headings' => $headings, 'rows' => $rows];
    }

    private static function equipmentSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Utilaj', 'Cantitate', 'Inceput', 'Sfarsit', 'Zile', 'Cost estimat', 'Rezervari suprapuse', 'Risc', 'Note'];

        $rows = $plans->map(fn (SiteEquipmentPlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Utilaj' => $plan->equipment?->name ?? '-',
            'Cantitate' => $plan->quantity,
            'Inceput' => self::formatDate($plan->usage_start),
            'Sfarsit' => self::formatDate($plan->usage_end),
            'Zile' => $plan->reserved_days ?? '-',
            'Cost estimat' => number_format((float) ($plan->estimated_cost ?? 0), 2) . ' lei',
            'Rezervari suprapuse' => $plan->reserved_elsewhere_count ?? 0,
            'Risc' => SiteEquipmentPlan::$riskLabels[$plan->risk_level] ?? $plan->risk_level,
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Utilaje', 'headings' => $headings, 'rows' => $rows];
    }

    private static function logisticsSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Categorie', 'Titlu', 'Locatie', 'Capacitate', 'Risc', 'Note'];

        $rows = $plans->map(fn (SiteLogisticsPlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Categorie' => SiteLogisticsPlan::$categoryLabels[$plan->category] ?? $plan->category,
            'Titlu' => $plan->title,
            'Locatie' => $plan->location_description ?? '-',
            'Capacitate' => $plan->capacity_notes ?? '-',
            'Risc' => SiteLogisticsPlan::$riskLabels[$plan->risk_level] ?? $plan->risk_level,
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Logistica', 'headings' => $headings, 'rows' => $rows];
    }

    private static function complianceSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Tip', 'Titlu', 'Subcontractor', 'Scadenta', 'Status', 'Note'];

        $rows = $plans->map(fn (SiteCompliancePlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Tip' => SiteCompliancePlan::$itemTypeLabels[$plan->item_type] ?? $plan->item_type,
            'Titlu' => $plan->title,
            'Subcontractor' => $plan->contractor?->name ?? '-',
            'Scadenta' => self::formatDate($plan->due_date),
            'Status' => SiteCompliancePlan::$statusLabels[$plan->status] ?? $plan->status,
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Documente & conformitate', 'headings' => $headings, 'rows' => $rows];
    }

    private static function budgetLinesSection(Collection $plans): array
    {
        $headings = ['Etapa', 'Categorie', 'Descriere', 'Cost', 'Note'];

        $rows = $plans->map(fn (SiteBudgetPlan $plan) => [
            'Etapa' => $plan->phase?->name ?? 'Fara etapa',
            'Categorie' => SiteBudgetPlan::$categoryLabels[$plan->category] ?? $plan->category,
            'Descriere' => $plan->description,
            'Cost' => number_format((float) $plan->estimated_cost, 2) . ' lei',
            'Note' => $plan->notes ?? '',
        ])->all();

        return ['name' => 'Buget - linii', 'headings' => $headings, 'rows' => $rows];
    }

    private static function budgetSummarySection(array $summary): array
    {
        $headings = ['Indicator', 'Valoare'];

        $rows = [
            ['Indicator' => 'Cost manopera (auto)', 'Valoare' => number_format($summary['labor_cost'] ?? 0, 2) . ' lei'],
            ['Indicator' => 'Cost materiale (auto)', 'Valoare' => number_format($summary['materials_cost'] ?? 0, 2) . ' lei'],
            ['Indicator' => 'Cost utilaje (auto)', 'Valoare' => number_format($summary['equipment_cost'] ?? 0, 2) . ' lei'],
            ['Indicator' => 'Cost manual adaugat', 'Valoare' => number_format($summary['manual_cost'] ?? 0, 2) . ' lei'],
            ['Indicator' => 'Total estimat', 'Valoare' => number_format($summary['total_estimated'] ?? 0, 2) . ' lei'],
            ['Indicator' => 'Buget alocat proiect', 'Valoare' => number_format($summary['project_budget'] ?? 0, 2) . ' lei'],
            ['Indicator' => 'Diferenta', 'Valoare' => number_format($summary['difference'] ?? 0, 2) . ' lei'],
        ];

        return ['name' => 'Buget - rezumat', 'headings' => $headings, 'rows' => $rows];
    }

    private static function readinessSection(array $readiness): array
    {
        $headings = ['Domeniu', 'Scor', 'Blocaje'];

        $rows = [
            ['Domeniu' => 'General', 'Scor' => $readiness['score'] . ' (' . $readiness['label'] . ')', 'Blocaje' => ''],
        ];

        foreach ($readiness['domains'] as $domain) {
            $rows[] = [
                'Domeniu' => $domain['label'],
                'Scor' => $domain['score'],
                'Blocaje' => implode('; ', $domain['blockers']),
            ];
        }

        return ['name' => 'Rezumat & scor de pregatire', 'headings' => $headings, 'rows' => $rows];
    }

    private static function aiSuggestionsSection(array $suggestions): array
    {
        $headings = ['Tip', 'Etapa', 'Sugestie'];

        $rows = [];

        foreach ($suggestions['staff'] as $item) {
            $rows[] = ['Tip' => 'Personal', 'Etapa' => $item['phase_name'], 'Sugestie' => $item['message']];
        }

        foreach ($suggestions['materials'] as $item) {
            $rows[] = ['Tip' => 'Materiale', 'Etapa' => $item['phase_name'], 'Sugestie' => $item['message']];
        }

        foreach ($suggestions['timeline'] as $item) {
            $rows[] = ['Tip' => 'Timeline', 'Etapa' => $item['phase_name'], 'Sugestie' => $item['message']];
        }

        return ['name' => 'AI Tools - sugestii', 'headings' => $headings, 'rows' => $rows];
    }

    private static function formatDate($value): string
    {
        if (! $value) {
            return '-';
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
