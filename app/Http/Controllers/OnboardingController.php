<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Team;
use App\Support\AnalyticsTracker;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->onboarding_completed_at !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Index', [
            'currentStep' => max(1, min(3, (int) $user->onboarding_step)),
            'onboardingData' => $user->onboarding_data ?? [],
        ]);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_type' => ['required', 'in:company,person'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $data = array_merge($user->onboarding_data ?? [], $validated);

        $user->onboarding_data = $data;
        $user->onboarding_step = max((int) $user->onboarding_step, 2);
        $user->save();

        return back()->with('success', 'Pasul 1 finalizat.');
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_name' => ['required', 'string', 'max:255'],
            'project_address' => ['nullable', 'string', 'max:255'],
            'project_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $tenantId = TenantContext::id($user);
        $data = array_merge($user->onboarding_data ?? [], $validated);

        $client = Client::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => $data['company_name'] ?? ($user->name . ' Company'),
            ],
            [
                'type' => $data['company_type'] ?? 'company',
                'phone' => $data['contact_phone'] ?? null,
                'active' => true,
            ]
        );

        if (empty($data['project_id']) || !Project::whereKey($data['project_id'])->exists()) {
            $project = Project::create([
                'tenant_id' => $tenantId,
                'client_id' => $client->id,
                'created_by' => $user->id,
                'name' => $validated['project_name'],
                'address' => $validated['project_address'] ?? null,
                'status' => 'active',
                'total_budget' => $validated['project_budget'] ?? null,
                'start_date' => now()->toDateString(),
            ]);

            AnalyticsTracker::track($request, 'first_project_created', [
                'project_id' => $project->id,
                'source' => 'onboarding_step_2',
            ], oncePerUser: true);

            $data['project_id'] = $project->id;
        }

        $user->onboarding_data = $data;
        $user->onboarding_step = max((int) $user->onboarding_step, 3);
        $user->save();

        return back()->with('success', 'Pasul 2 finalizat.');
    }

    public function storeStep3(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'team_name' => ['required', 'string', 'max:255'],
            'team_specialty' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $tenantId = TenantContext::id($user);
        $data = array_merge($user->onboarding_data ?? [], $validated);

        if (empty($data['team_id']) || !Team::whereKey($data['team_id'])->exists()) {
            $team = Team::create([
            'tenant_id' => $tenantId,
                'name' => $validated['team_name'],
                'specialty' => $validated['team_specialty'] ?? null,
                'leader_id' => $user->id,
                'active' => true,
            ]);

            $data['team_id'] = $team->id;
        }

        $user->onboarding_data = $data;
        $user->onboarding_step = 3;
        $user->onboarding_completed_at = now();
        $user->save();

        AnalyticsTracker::track($request, 'onboarding_completed', [], oncePerUser: true);

        return redirect()->route('dashboard')->with('success', 'Onboarding finalizat cu succes!');
    }
}
