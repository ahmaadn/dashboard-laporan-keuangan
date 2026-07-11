<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanSeeDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canSeeDashboard()) {
            abort(403, 'Anda tidak memiliki akses ke dashboard.');
        }

        return $next($request);
    }
}
