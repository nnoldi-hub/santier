<?php

namespace App\Support;

use App\Models\Quote;

class QuotePdfPresenter
{
    /**
     * Derives every computed value the quote PDF layouts need from the quote,
     * its meta JSON, and its cost breakdown - shared by all layout variants so
     * the calculation logic (stage totals, pricing scenarios, timeline base)
     * lives in one place instead of being duplicated per template.
     */
    public static function present(Quote $quote, array $meta, array $breakdown, ?string $notes, array $branding): array
    {
        $quoteItems = $quote->items ?? collect();
        $stagesMeta = is_array($meta['stages'] ?? null) ? $meta['stages'] : [];
        $smartInputs = is_array($meta['smart_inputs'] ?? null) ? $meta['smart_inputs'] : [];
        $indirectCosts = is_array($meta['indirect_costs'] ?? null) ? $meta['indirect_costs'] : [];
        $optionalFeatures = is_array($meta['optional_features'] ?? null) ? $meta['optional_features'] : [];
        $timelineDays = (int) ($meta['timeline_days_total'] ?? 0);
        $clientName = $meta['client_name'] ?? null;
        $packageTier = strtoupper((string) ($meta['package_tier'] ?? 'STANDARD'));
        $projectType = strtoupper((string) ($meta['project_type'] ?? 'N/A'));
        $recommendedMargin = (float) ($meta['recommended_margin_pct'] ?? ($meta['min_margin_pct'] ?? 0));
        $documentIssuer = trim((string) ($branding['document_issuer_name'] ?? ''));
        $materialMode = (string) ($meta['material_mode'] ?? 'capped_allowance');
        $materialCaps = is_array($meta['material_caps'] ?? null) ? $meta['material_caps'] : [];
        $pricingScenarios = is_array($meta['pricing_scenarios'] ?? null) ? $meta['pricing_scenarios'] : [];

        $totals = is_array($breakdown['totals'] ?? null) ? $breakdown['totals'] : [];
        $materialsTotal = (float) ($totals['materials_cost'] ?? 0);
        $laborTotal = (float) ($totals['labor_cost'] ?? 0);
        $equipmentTotal = (float) ($totals['equipment_cost'] ?? 0);

        $indirectAndOptionTotal = 0.0;
        foreach ($indirectCosts as $row) {
            $indirectAndOptionTotal += (float) ($row['amount'] ?? 0);
        }
        foreach ($optionalFeatures as $row) {
            if (!empty($row['enabled'])) {
                $indirectAndOptionTotal += (float) ($row['amount'] ?? 0);
            }
        }

        $stageRows = collect($stagesMeta)->map(function ($stage) use ($quoteItems) {
            $name = (string) ($stage['name'] ?? 'Etapa');
            $days = (float) ($stage['duration_days'] ?? 0);
            $rows = $quoteItems->where('stage_name', $name);

            $materialSell = (float) $rows->where('item_type', 'material')->sum('line_sell_total');
            $laborSell = (float) $rows->where('item_type', 'labor')->sum('line_sell_total');
            $equipmentSell = (float) $rows->where('item_type', 'equipment')->sum('line_sell_total');
            $otherSell = (float) $rows->whereNotIn('item_type', ['material', 'labor', 'equipment'])->sum('line_sell_total');

            return [
                'name' => $name,
                'days' => $days,
                'materials' => $materialSell,
                'labor' => $laborSell,
                'equipment' => $equipmentSell,
                'indirect' => $otherSell,
                'total' => $materialSell + $laborSell + $equipmentSell + $otherSell,
            ];
        })->values();

        $timelineBase = max((float) $stageRows->max('days'), 1);
        $profitValue = (float) $quote->total_net - ($materialsTotal + $laborTotal + $equipmentTotal + $indirectAndOptionTotal);
        $profitMargin = (float) $quote->total_net > 0 ? ($profitValue / (float) $quote->total_net) * 100 : 0;

        $defaultCappedMaterialsTotal = ((float) ($smartInputs['walls_area'] ?? 0) * (float) ($materialCaps['paint_max_per_mp'] ?? 18))
            + ((float) ($smartInputs['floor_area'] ?? 0) * (float) ($materialCaps['parquet_max_per_mp'] ?? 100))
            + ((float) ($smartInputs['tile_area'] ?? 0) * (float) ($materialCaps['tile_max_per_mp'] ?? 80));
        $laborOnlyTotal = (float) ($pricingScenarios['labor_only_total'] ?? ((float) $quoteItems->where('item_type', '!=', 'material')->sum('line_sell_total')));
        $cappedMaterialsTotal = (float) ($pricingScenarios['capped_materials_total'] ?? $defaultCappedMaterialsTotal);
        $withCappedMaterialsTotal = (float) ($pricingScenarios['with_capped_materials_total'] ?? ($laborOnlyTotal + $cappedMaterialsTotal));

        return [
            'smartInputs' => $smartInputs,
            'optionalFeatures' => $optionalFeatures,
            'timelineDays' => $timelineDays,
            'clientName' => $clientName,
            'packageTier' => $packageTier,
            'projectType' => $projectType,
            'recommendedMargin' => $recommendedMargin,
            'documentIssuer' => $documentIssuer,
            'materialMode' => $materialMode,
            'materialCaps' => $materialCaps,
            'materialsTotal' => $materialsTotal,
            'laborTotal' => $laborTotal,
            'equipmentTotal' => $equipmentTotal,
            'indirectAndOptionTotal' => $indirectAndOptionTotal,
            'stageRows' => $stageRows,
            'timelineBase' => $timelineBase,
            'profitValue' => $profitValue,
            'profitMargin' => $profitMargin,
            'laborOnlyTotal' => $laborOnlyTotal,
            'cappedMaterialsTotal' => $cappedMaterialsTotal,
            'withCappedMaterialsTotal' => $withCappedMaterialsTotal,
            'notes' => $notes,
        ];
    }
}
