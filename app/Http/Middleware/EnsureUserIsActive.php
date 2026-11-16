<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware "active". If an admin deactivates a user while that user is logged in,
 * their very next request logs them out and sends them to the login page with a message.
 */
class EnsureUserIsActive
{
    public const MESSAGE = 'Your account has been deactivated. Please contact the lab custodian.';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
