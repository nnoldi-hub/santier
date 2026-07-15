<?php

namespace App\Support;

use Illuminate\Support\Collection;

class QuoteBreakdownResolver
{
    public const AI_BREAKDOWN_MARKER = '[AI_BREAKDOWN_JSON]';

    public static function buildBreakdownFromItems(Collection $items): array
    {
        $materials = [];
        $labor = [];
        $equipment = [];
        $materialsTotal = 0.0;
        $laborTotal = 0.0;
        $equipmentTotal = 0.0;

        foreach ($items as $item) {
            $lineTotal = (float) $item->line_sell_total;

            if ($item->item_type === 'material') {
                $materials[] = [
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->sell_unit_price,
                    'estimated_cost' => $lineTotal,
                ];
                $materialsTotal += $lineTotal;
                continue;
            }

            if ($item->item_type === 'equipment') {
                $equipment[] = [
                    'name' => $item->name,
                    'estimated_hours' => (float) $item->quantity,
                    'hour_rate' => (float) $item->sell_unit_price,
                    'estimated_cost' => $lineTotal,
                ];
                $equipmentTotal += $lineTotal;
                continue;
            }

            $labor[] = [
                'name' => $item->name,
                'estimated_hours' => (float) $item->quantity,
                'hour_rate' => (float) $item->sell_unit_price,
                'estimated_cost' => $lineTotal,
            ];
            $laborTotal += $lineTotal;
        }

        return [
            'materials' => $materials,
            'labor' => $labor,
            'equipment' => $equipment,
            'totals' => [
                'materials_cost' => round($materialsTotal, 2),
                'labor_cost' => round($laborTotal, 2),
                'equipment_cost' => round($equipmentTotal, 2),
                'total_net' => round($materialsTotal + $laborTotal + $equipmentTotal, 2),
            ],
        ];
    }

    public static function extractBreakdownFromNotes(string $notes): array
    {
        if ($notes === '' || !str_contains($notes, self::AI_BREAKDOWN_MARKER)) {
            return [trim($notes), null];
        }

        $parts = explode(self::AI_BREAKDOWN_MARKER, $notes, 2);
        $plainNotes = trim($parts[0]);
        $jsonRaw = trim($parts[1] ?? '');
        $decoded = json_decode($jsonRaw, true);

        if (!is_array($decoded)) {
            return [$plainNotes, null];
        }

        return [$plainNotes, $decoded];
    }
}
