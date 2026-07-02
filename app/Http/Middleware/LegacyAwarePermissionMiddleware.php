<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;

class LegacyAwarePermissionMiddleware extends PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = null)
    {
        $user = $request->user();

        if ($user && $user->roles()->count() === 0 && $user->permissions()->count() === 0) {
            return $next($request);
        }

        return parent::handle($request, $next, $permission, $guard);
    }
}
