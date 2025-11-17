<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantIsolation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Set tenant context globally for query scopes
        // This ensures all queries are automatically filtered by tenant_id
        $request->merge(['tenant_id' => $user->tenant_id]);

        // Share tenant_id with views if needed
        view()->share('tenant_id', $user->tenant_id);

        return $next($request);
    }
}
