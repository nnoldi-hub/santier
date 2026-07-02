<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        $teams = Team::where('tenant_id', $tenantId)
            ->with(['leader:id,name', 'members.user:id,name'])
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Teams/Create', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());

        Team::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return redirect()->route('teams.index')->with('success', 'Echipa creata cu succes!');
    }

    public function show(Team $team): Response
    {
        $team->load(['leader:id,name', 'members.user:id,name']);

        return Inertia::render('Teams/Show', [
            'team' => $team,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Team $team): Response
    {
        return Inertia::render('Teams/Edit', [
            'team' => $team,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreTeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validated());

        return redirect()->route('teams.index')->with('success', 'Echipa actualizata!');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Echipa stearsa!');
    }

    public function storeMember(StoreTeamMemberRequest $request, Team $team): RedirectResponse
    {
        $data = $request->validated();

        $exists = $team->members()->where('user_id', $data['user_id'])->whereNull('left_at')->exists();
        if ($exists) {
            return back()->withErrors(['user_id' => 'Utilizatorul este deja membru activ in aceasta echipa.']);
        }

        $team->members()->create([
            ...$data,
            'joined_at' => $data['joined_at'] ?? now()->toDateString(),
        ]);

        return back()->with('success', 'Membru adaugat in echipa!');
    }

    public function removeMember(Team $team, TeamMember $member): RedirectResponse
    {
        if ((int) $member->team_id !== (int) $team->id) {
            abort(404);
        }

        $member->delete();

        return back()->with('success', 'Membru eliminat din echipa!');
    }
}
