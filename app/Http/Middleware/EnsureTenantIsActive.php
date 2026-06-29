<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->tenant_id && $user->tenant) {
            if ($user->tenant->isSuspended()) {
                auth()->logout();
                $request->session()->invalidate();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your organisation account has been suspended. Please contact support.']);
            }
        }

        if ($user && $user->status !== 'active') {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account is not active. Please contact your administrator.']);
        }

        return $next($request);
    }
}
