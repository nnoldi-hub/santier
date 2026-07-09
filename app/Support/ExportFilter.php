<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportFilter
{
    private const QUICK_RANGES = [
        'today',
        'last_7d',
        'last_30d',
        'last_90d',
        'this_year',
    ];

    public static function fromRequest(Request $request): array
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $projectId = $request->integer('project_id');
        $teamId = $request->integer('team_id');
        $quickRange = self::normalizeQuickRange($request->string('quick_range')->toString());
        $globalSearch = trim($request->string('global_search')->toString());
        $legacySearch = trim($request->string('q')->toString());
        $resolvedSearch = $globalSearch !== '' ? $globalSearch : $legacySearch;

        [$from, $to] = self::resolveDateRange($from, $to, $quickRange);

        return [
            'from' => $from !== '' ? $from : null,
            'to' => $to !== '' ? $to : null,
            'quick_range' => $quickRange,
            'status' => self::csvToArray($request->string('status')->toString()),
            'priority' => self::csvToArray($request->string('priority')->toString()),
            'assignee_ids' => self::csvToIntArray($request->string('assignee_ids')->toString()),
            'project_id' => $projectId > 0 ? $projectId : null,
            'team_id' => $teamId > 0 ? $teamId : null,
            'include_inactive' => $request->boolean('include_inactive'),
            'global_search' => $resolvedSearch !== '' ? $resolvedSearch : null,
            'q' => $resolvedSearch !== '' ? $resolvedSearch : null,
        ];
    }

    public static function csvToArray(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    public static function csvToIntArray(string $value): array
    {
        return array_values(array_filter(array_map(fn ($item) => (int) trim($item), explode(',', $value)), fn ($n) => $n > 0));
    }

    private static function normalizeQuickRange(string $quickRange): ?string
    {
        $normalized = trim($quickRange);

        if ($normalized === '' || !in_array($normalized, self::QUICK_RANGES, true)) {
            return null;
        }

        return $normalized;
    }

    private static function resolveDateRange(string $from, string $to, ?string $quickRange): array
    {
        if ($quickRange === null) {
            return [$from, $to];
        }

        $today = Carbon::now();

        $resolved = match ($quickRange) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'last_7d' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            'last_30d' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            'last_90d' => [$today->copy()->subDays(89)->toDateString(), $today->toDateString()],
            'this_year' => [$today->copy()->startOfYear()->toDateString(), $today->toDateString()],
            default => [$from, $to],
        };

        return [
            $from !== '' ? $from : $resolved[0],
            $to !== '' ? $to : $resolved[1],
        ];
    }
}
