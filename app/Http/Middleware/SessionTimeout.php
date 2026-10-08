<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class SessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $timeout = 1800; // 30 minutes in seconds

        if (Auth::check()) {
            $lastActivity = session('last_activity');
            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Your session has expired due to inactivity.');
            }
            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}
