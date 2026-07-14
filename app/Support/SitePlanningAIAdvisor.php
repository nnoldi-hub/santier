<?php

namespace App\Support;

use Illuminate\Support\Collection;

class SitePlanningAIAdvisor
{
    private const DEFAULT_DURATION_DAYS = 10;

    private const PHASE_TYPE_CATALOG = [
        'demolare' => [
            'specialty' => 'Echipa demolari',
            'materials' => ['Recipiente deseuri', 'Echipament protectie'],
            'duration_range' => [3, 7],
        ],
        'structura' => [
            'specialty' => 'Fierar-betonist',
            'materials' => ['Beton', 'Otel beton', 'Cofraje'],
            'duration_range' => [20, 40],
        ],
        'instalatii_brute' => [
            'specialty' => 'Instalator',
            'materials' => ['Tevi', 'Cabluri electrice'],
            'duration_range' => [10, 20],
        ],
        'tencuieli' => [
            'specialty' => 'Tencuitor',
            'materials' => ['Tencuiala', 'Plasa tencuiala'],
            'duration_range' => [7, 14],
        ],
        'sape' => [
            'specialty' => 'Sapar',
            'materials' => ['Sapa autonivelanta'],
            'duration_range' => [3, 7],
        ],
        'glet' => [
            'specialty' => 'Gletuitor',
            'materials' => ['Glet'],
            'duration_range' => [5, 10],
        ],
        'finisaje_umede' => [
            'specialty' => 'Placator',
            'materials' => ['Adeziv gresie/faianta', 'Gresie/Faianta'],
            'duration_range' => [7, 14],
        ],
        'montaj_tamplarie' => [
            'specialty' => 'Tamplar',
            'materials' => ['Tamplarie PVC/Aluminiu'],
            'duration_range' => [3, 7],
        ],
        'zugraveli' => [
            'specialty' => 'Zugrav',
            'materials' => ['Vopsea lavabila', 'Grund'],
            'duration_range' => [5, 10],
        ],
        'pardoseli' => [
            'specialty' => 'Parchetar',
            'materials' => ['Parchet/Gresie', 'Adeziv pardoseala'],
            'duration_range' => [5, 10],
        ],
        'finisaje_fine' => [
            'specialty' => 'Finisor',
            'materials' => ['Materiale finisaje'],
            'duration_range' => [5, 10],
        ],
        'curatenie' => [
            'specialty' => 'Echipa curatenie',
            'materials' => ['Materiale curatenie'],
            'duration_range' => [1, 3],
        ],
    ];

    public static function suggest(Collection $phases, Collection $staffPlans, Collection $materialPlans): array
    {
        $catalogPhases = $phases->filter(fn ($phase) => array_key_exists($phase->type, self::PHASE_TYPE_CATALOG));

        return [
            'staff' => self::suggestStaff($catalogPhases, $staffPlans),
            'materials' => self::suggestMaterials($catalogPhases, $materialPlans),
            'timeline' => self::suggestTimeline($catalogPhases),
        ];
    }

    private static function suggestStaff(Collection $phases, Collection $staffPlans): array
    {
        $phaseIdsWithPlans = $staffPlans->pluck('phase_id')->filter()->unique();

        return $phases->reject(fn ($phase) => $phaseIdsWithPlans->contains($phase->id))
            ->map(function ($phase) {
                $catalogEntry = self::PHASE_TYPE_CATALOG[$phase->type];
                $days = $phase->duration_days ?: self::DEFAULT_DURATION_DAYS;
                $headcount = max(1, (int) ceil($days / 15));

                return [
                    'phase_id' => $phase->id,
                    'phase_name' => $phase->name,
                    'message' => "Nicio planificare de personal. Sugestie: {$catalogEntry['specialty']}, {$headcount} persoana(e).",
                ];
            })
            ->values()
            ->all();
    }

    private static function suggestMaterials(Collection $phases, Collection $materialPlans): array
    {
        $phaseIdsWithPlans = $materialPlans->pluck('phase_id')->filter()->unique();

        return $phases->reject(fn ($phase) => $phaseIdsWithPlans->contains($phase->id))
            ->map(function ($phase) {
                $catalogEntry = self::PHASE_TYPE_CATALOG[$phase->type];
                $materialsList = implode(', ', $catalogEntry['materials']);

                return [
                    'phase_id' => $phase->id,
                    'phase_name' => $phase->name,
                    'message' => "Niciun plan de materiale. Materiale tipice: {$materialsList}.",
                ];
            })
            ->values()
            ->all();
    }

    private static function suggestTimeline(Collection $phases): array
    {
        return $phases->filter(function ($phase) {
            [$min, $max] = self::PHASE_TYPE_CATALOG[$phase->type]['duration_range'];

            return ! $phase->duration_days || $phase->duration_days < $min || $phase->duration_days > $max;
        })
            ->map(function ($phase) {
                [$min, $max] = self::PHASE_TYPE_CATALOG[$phase->type]['duration_range'];

                if (! $phase->duration_days) {
                    $message = "Nicio durata planificata. Tipic pentru acest tip de etapa: {$min}-{$max} zile.";
                } else {
                    $message = "Durata planificata ({$phase->duration_days} zile) iese din intervalul tipic ({$min}-{$max} zile).";
                }

                return [
                    'phase_id' => $phase->id,
                    'phase_name' => $phase->name,
                    'message' => $message,
                ];
            })
            ->values()
            ->all();
    }
}
