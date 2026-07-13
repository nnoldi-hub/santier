<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ExportChartBuilder
{
    private const CATEGORICAL_FIELDS = [
        'projects' => [
            ['field' => 'status', 'title' => 'Distributie status'],
        ],
        'quotes' => [
            ['field' => 'status', 'title' => 'Distributie status'],
        ],
        'resource-comparison' => [
            ['field' => 'status', 'title' => 'Distributie status comenzi'],
        ],
        'wbs' => [
            ['field' => 'status', 'title' => 'Distributie status etape'],
        ],
        'stage-progress' => [
            ['field' => 'status', 'title' => 'Distributie status etape'],
        ],
        'stage-tasks' => [
            ['field' => 'status', 'title' => 'Distributie status'],
        ],
        'tasks' => [
            ['field' => 'status', 'title' => 'Distributie status'],
            ['field' => 'priority', 'title' => 'Distributie prioritate'],
        ],
        'defects' => [
            ['field' => 'status', 'title' => 'Distributie status'],
            ['field' => 'priority', 'title' => 'Distributie prioritate'],
        ],
        'equipment' => [
            ['field' => 'availability_status', 'title' => 'Disponibilitate utilaje'],
        ],
        'documents' => [
            ['field' => 'payment_status', 'title' => 'Status plata'],
        ],
        'materials' => [
            ['field' => 'active', 'title' => 'Materiale active vs inactive', 'boolean' => true],
        ],
        'teams' => [
            ['field' => 'active', 'title' => 'Membri activi vs inactivi', 'boolean' => true],
        ],
    ];

    public static function build(string $exportType, Collection $rows): array
    {
        $configs = self::CATEGORICAL_FIELDS[$exportType] ?? [];
        $charts = [];

        foreach ($configs as $config) {
            $chart = self::buildChart($exportType, $config, $rows);

            if ($chart !== null) {
                $charts[] = $chart;
            }
        }

        return $charts;
    }

    private static function buildChart(string $exportType, array $config, Collection $rows): ?array
    {
        $field = $config['field'];
        $isBoolean = (bool) ($config['boolean'] ?? false);

        $values = $rows
            ->map(fn (mixed $row) => self::resolveField($row, $field))
            ->filter(fn ($value) => $value !== null && $value !== '');

        if ($values->isEmpty()) {
            return null;
        }

        if ($isBoolean) {
            $values = $values->map(fn ($value) => $value ? 'Activ' : 'Inactiv');
        }

        $counts = $values->countBy()->sortDesc();

        return [
            'key' => $exportType . '_' . $field,
            'title' => $config['title'],
            'type' => 'bar',
            'labels' => $counts->keys()->values()->all(),
            'series' => $counts->values()->all(),
        ];
    }

    private static function resolveField(mixed $row, string $field): mixed
    {
        if (is_array($row)) {
            return Arr::get($row, $field);
        }

        if (is_object($row)) {
            return $row->{$field} ?? null;
        }

        return null;
    }
}
