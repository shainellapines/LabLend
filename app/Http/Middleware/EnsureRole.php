<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "role". Usage in routes: ->middleware('role:admin') or 'role:admin,faculty'.
 * Anyone whose role is not in the list gets a 403 Forbidden page.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->hasRole(...$roles), 403);

        return $next($request);
    }
}
