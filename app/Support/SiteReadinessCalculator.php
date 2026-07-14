<?php

namespace App\Support;

use App\Models\SiteLogisticsPlan;
use Illuminate\Support\Collection;

class SiteReadinessCalculator
{
    private const DOMAIN_LABELS = [
        'staff' => 'Echipe & specialitati',
        'contractors' => 'Subcontractori',
        'materials' => 'Materiale',
        'equipment' => 'Utilaje',
        'logistics' => 'Logistica',
        'compliance' => 'Documente & conformitate',
        'budget' => 'Buget',
    ];

    public static function calculate(
        Collection $staffPlans,
        Collection $contractorPlans,
        Collection $materialPlans,
        Collection $equipmentPlans,
        Collection $logisticsPlans,
        Collection $compliancePlans,
        array $budgetSummary
    ): array {
        $domains = [
            'staff' => self::staffDomain($staffPlans),
            'contractors' => self::contractorsDomain($contractorPlans),
            'materials' => self::materialsDomain($materialPlans),
            'equipment' => self::equipmentDomain($equipmentPlans),
            'logistics' => self::logisticsDomain($logisticsPlans),
            'compliance' => self::complianceDomain($compliancePlans),
            'budget' => self::budgetDomain($budgetSummary),
        ];

        foreach ($domains as $key => $domain) {
            $domains[$key] = [
                'key' => $key,
                'label' => self::DOMAIN_LABELS[$key],
                'score' => $domain['score'],
                'blockers' => $domain['blockers'],
            ];
        }

        $overallScore = (int) round(collect($domains)->avg('score'));

        $blockers = [];
        foreach ($domains as $domain) {
            foreach ($domain['blockers'] as $blocker) {
                $blockers[] = $domain['label'] . ': ' . $blocker;
            }
        }

        return [
            'score' => $overallScore,
            'label' => self::scoreLabel($overallScore),
            'domains' => $domains,
            'blockers' => $blockers,
        ];
    }

    private static function staffDomain(Collection $plans): array
    {
        $total = $plans->count();

        if ($total === 0) {
            return ['score' => 0, 'blockers' => ['Nicio planificare de personal.']];
        }

        $highRisk = $plans->where('risk_level', 'high')->count();
        $blockers = [];

        if ($highRisk > 0) {
            $blockers[] = "{$highRisk} plan(uri) de personal cu risc ridicat.";
        }

        return ['score' => (int) round(100 - ($highRisk / $total * 100)), 'blockers' => $blockers];
    }

    private static function contractorsDomain(Collection $plans): array
    {
        $total = $plans->count();

        if ($total === 0) {
            return ['score' => 0, 'blockers' => ['Niciun plan de subcontractor.']];
        }

        $ready = $plans->filter(fn ($plan) => $plan->contract_status === 'signed' && $plan->availability_status === 'ok')->count();
        $missing = $plans->where('contract_status', 'missing')->count();
        $conflict = $plans->where('availability_status', 'conflict')->count();

        $blockers = [];
        if ($missing > 0) {
            $blockers[] = "{$missing} subcontractor(i) fara contract.";
        }
        if ($conflict > 0) {
            $blockers[] = "{$conflict} subcontractor(i) cu disponibilitate in conflict.";
        }

        return ['score' => (int) round($ready / $total * 100), 'blockers' => $blockers];
    }

    private static function materialsDomain(Collection $plans): array
    {
        $total = $plans->count();

        if ($total === 0) {
            return ['score' => 0, 'blockers' => ['Niciun plan de material.']];
        }

        $highRisk = $plans->where('risk_level', 'high')->count();
        $blockers = [];

        if ($highRisk > 0) {
            $blockers[] = "{$highRisk} plan(uri) de material cu risc ridicat.";
        }

        return ['score' => (int) round(100 - ($highRisk / $total * 100)), 'blockers' => $blockers];
    }

    private static function equipmentDomain(Collection $plans): array
    {
        $total = $plans->count();

        if ($total === 0) {
            return ['score' => 0, 'blockers' => ['Niciun plan de utilaj.']];
        }

        $highRisk = $plans->where('risk_level', 'high')->count();
        $overlapping = $plans->filter(fn ($plan) => ($plan->reserved_elsewhere_count ?? 0) > 0)->count();
        $problematic = $plans->filter(fn ($plan) => $plan->risk_level === 'high' || ($plan->reserved_elsewhere_count ?? 0) > 0)->count();

        $blockers = [];
        if ($highRisk > 0) {
            $blockers[] = "{$highRisk} plan(uri) de utilaj cu risc ridicat.";
        }
        if ($overlapping > 0) {
            $blockers[] = "{$overlapping} plan(uri) de utilaj cu rezervari suprapuse.";
        }

        return ['score' => (int) round(100 - ($problematic / $total * 100)), 'blockers' => $blockers];
    }

    private static function logisticsDomain(Collection $plans): array
    {
        $categories = array_keys(SiteLogisticsPlan::$categoryLabels);
        $covered = $plans->pluck('category')->unique()->intersect($categories);
        $missing = array_diff($categories, $covered->all());

        $blockers = [];
        if (! empty($missing)) {
            $missingLabels = array_map(fn ($key) => SiteLogisticsPlan::$categoryLabels[$key], $missing);
            $blockers[] = 'Categorii neacoperite: ' . implode(', ', $missingLabels) . '.';
        }

        return ['score' => (int) round(count($covered) / count($categories) * 100), 'blockers' => $blockers];
    }

    private static function complianceDomain(Collection $plans): array
    {
        $total = $plans->count();

        if ($total === 0) {
            return ['score' => 0, 'blockers' => ['Niciun element de conformitate.']];
        }

        $valid = $plans->where('status', 'valid')->count();
        $expired = $plans->where('status', 'expired')->count();
        $missing = $plans->where('status', 'missing')->count();

        $blockers = [];
        if ($expired > 0) {
            $blockers[] = "{$expired} element(e) de conformitate expirate.";
        }
        if ($missing > 0) {
            $blockers[] = "{$missing} element(e) de conformitate lipsa.";
        }

        return ['score' => (int) round($valid / $total * 100), 'blockers' => $blockers];
    }

    private static function budgetDomain(array $budgetSummary): array
    {
        $projectBudget = (float) ($budgetSummary['project_budget'] ?? 0);
        $difference = (float) ($budgetSummary['difference'] ?? 0);

        if ($projectBudget <= 0) {
            return ['score' => 50, 'blockers' => ['Bugetul alocat proiectului nu este setat.']];
        }

        if ($difference >= 0) {
            return ['score' => 100, 'blockers' => []];
        }

        $overrun = abs($difference);
        $score = max(0, (int) round(100 - ($overrun / $projectBudget * 100)));

        return [
            'score' => $score,
            'blockers' => ['Estimarea depaseste bugetul alocat cu ' . number_format($overrun, 2) . ' lei.'],
        ];
    }

    private static function scoreLabel(int $score): string
    {
        if ($score >= 80) {
            return 'Pregatit';
        }

        if ($score >= 50) {
            return 'Necesita atentie';
        }

        return 'Nepregatit';
    }
}
