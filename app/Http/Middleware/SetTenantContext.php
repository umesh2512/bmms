<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;

        if (auth()->check()) {
            $tenantId = auth()->user()->tenant_id;
        }

        app()->instance('current_tenant_id', $tenantId);

        return $next($request);
    }
}
