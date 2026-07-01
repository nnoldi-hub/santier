<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Team;
use App\Http\Requests\StoreProjectRequest;
use App\Support\AnalyticsTracker;
use App\Support\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::with('client')
            ->where('tenant_id', 1)
            ->latest()
            ->paginate(15);

        return Inertia::render('Projects/Index', ['projects' => $projects]);
    }

    public function create(): Response
    {
        $clients = Client::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'type']);
        return Inertia::render('Projects/Create', ['clients' => $clients]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        if (!PricingPlan::canCreateProject($request->user())) {
            return back()
                ->withInput()
                ->with('error', PricingPlan::projectLimitMessage($request->user()));
        }

        $data = $request->validated();
        $data['tenant_id'] = 1;
        $data['created_by'] = $request->user()->id;
        $project = Project::create($data);

        $userProjectCount = Project::query()
            ->where('tenant_id', 1)
            ->where('created_by', $request->user()->id)
            ->count();

        if ($userProjectCount === 1) {
            AnalyticsTracker::track($request, 'first_project_created', [
                'project_id' => $project->id,
            ], oncePerUser: true);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Proiect creat cu succes!');
    }

    public function show(Project $project): Response
    {
        $project->load([
            'client',
            'creator',
            'phases.assignments.team',
            'phases.contractor',
            'phases.equipmentReservations.equipment',
            'tasks.assignee',
            'tasks.phase',
            'defects.assignee',
            'defects.phase',
        ]);

        return Inertia::render('Projects/Show', [
            'project'    => $project,
            'typeLabels' => ProjectPhase::$typeLabels,
            'teams' => Team::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'type']),
            'equipment' => Equipment::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'cost_per_hour', 'availability_status']),
        ]);
    }

    public function edit(Project $project): Response
    {
        $clients = Client::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'type']);
        return Inertia::render('Projects/Edit', ['project' => $project, 'clients' => $clients]);
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());
        return redirect()->route('projects.show', $project)->with('success', 'Proiect actualizat!');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Proiect sters!');
    }
}