<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! TenantContext::isPlatformAdmin($request->user())) {
            return $next($request);
        }

        $routeName = (string) $request->route()?->getName();
        $platformRoute = str_starts_with($routeName, 'admin.')
            || str_starts_with($routeName, 'pilot-invites.');

        abort_unless($platformRoute, 403);

        return $next($request);
    }
}
