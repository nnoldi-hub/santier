<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AccessAudit;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_unless(TenantContext::isPlatformAdmin($request->user()), 403);
        abort_if($user->is_superadmin || TenantContext::isPlatformAdmin($user), 422, 'Nu poti impersona un administrator de platforma.');

        $tenantId = (int) ($user->current_tenant_id ?: $user->tenant_id);
        abort_if($tenantId <= 0, 422, 'Utilizatorul nu are o firma asociata.');
        abort_unless($user->tenantMemberships()->where('tenant_id', $tenantId)->where('status', 'active')->exists(), 422, 'Utilizatorul nu are acces activ la firma.');

        $admin = $request->user();
        $request->session()->put('impersonation.admin_id', $admin->id);
        $request->session()->put('impersonation.started_at', now()->toIso8601String());
        Auth::login($user);
        $request->session()->regenerate();

        AccessAudit::log('platform.impersonation.started', $admin, $request, 'user', $user->id, [
            'target_user_id' => $user->id,
            'target_tenant_id' => $tenantId,
        ]);

        return redirect()->route('dashboard')->with('success', 'Sesiune de suport pornita pentru ' . $user->name . '.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = (int) $request->session()->get('impersonation.admin_id', 0);
        abort_if($adminId <= 0, 404);

        $target = $request->user();
        $admin = User::query()->findOrFail($adminId);
        abort_unless(TenantContext::isPlatformAdmin($admin), 403);

        AccessAudit::log('platform.impersonation.stopped', $admin, $request, 'user', $target?->id, [
            'target_user_id' => $target?->id,
            'target_tenant_id' => $target ? TenantContext::id($target) : null,
            'started_at' => $request->session()->get('impersonation.started_at'),
        ]);

        Auth::login($admin);
        $request->session()->forget(['impersonation.admin_id', 'impersonation.started_at']);
        $request->session()->regenerate();

        return redirect()->route('admin.index')->with('success', 'Ai revenit in contul Superadmin.');
    }
}
