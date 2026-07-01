<?php

namespace App\Support;

use Illuminate\Http\Request;

class ExportFilter
{
    public static function fromRequest(Request $request): array
    {
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $projectId = $request->integer('project_id');
        $teamId = $request->integer('team_id');

        return [
            'from' => $from !== '' ? $from : null,
            'to' => $to !== '' ? $to : null,
            'status' => self::csvToArray($request->string('status')->toString()),
            'priority' => self::csvToArray($request->string('priority')->toString()),
            'assignee_ids' => self::csvToIntArray($request->string('assignee_ids')->toString()),
            'project_id' => $projectId > 0 ? $projectId : null,
            'team_id' => $teamId > 0 ? $teamId : null,
            'include_inactive' => $request->boolean('include_inactive'),
            'q' => trim($request->string('q')->toString()) ?: null,
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
}
