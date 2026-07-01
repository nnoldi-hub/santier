<?php

namespace App\Http\Controllers;

use App\Support\ExportDatasetBuilder;
use App\Support\ExportFilter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CostTrackingController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('costs', $filters);
        $projects = $dataset['rows'];

        $summary = [
            'projects_count' => $projects->count(),
            'budget_total' => (float) $projects->sum(fn (array $row) => (float) ($row['budget'] ?? 0)),
            'quotes_total' => (float) $projects->sum(fn (array $row) => (float) ($row['total_gross'] ?? 0)),
            'accepted_total' => (float) $projects->sum(fn (array $row) => (float) ($row['accepted_total_gross'] ?? 0)),
            'over_budget_count' => $projects->filter(fn (array $row) => ($row['diff_vs_budget'] ?? null) !== null && (float) $row['diff_vs_budget'] > 0)->count(),
        ];

        return Inertia::render('CostTracking/Index', [
            'projects' => $projects,
            'filters' => $filters,
            'summary' => $summary,
        ]);
    }
}
