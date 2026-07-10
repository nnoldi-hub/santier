<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;

class LegacyAwarePermissionMiddleware extends PermissionMiddleware
{
    public function handle(Request $request, Closure $next, $permission, $guard = null)
    {
        return parent::handle($request, $next, $permission, $guard);
    }
}
