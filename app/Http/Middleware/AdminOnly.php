<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect('/')->with('error', 'You must be logged in to access the dashboard.');
        }

        // Check if user has ADMIN role
        if (auth()->user()->role !== 'ADMIN') {
            return redirect('/')->with('error', 'You do not have permission to access the dashboard. Admin access required.');
        }

        return $next($request);
    }
}
