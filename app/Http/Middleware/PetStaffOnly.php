<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PetStaffOnly
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
            return redirect('/')->with('error', 'You must be logged in to access this resource.');
        }

        // Check if user has PET_STAFF or SUPER_ADMIN role
        $userRole = auth()->user()->role;
        if ($userRole !== 'PET_STAFF' && $userRole !== 'SUPER_ADMIN' && $userRole !== 'pet_staff' && $userRole !== 'super_admin') {
            return redirect('/')->with('error', 'You do not have permission to access this resource. PET_STAFF or SUPER_ADMIN access required.');
        }

        return $next($request);
    }
}
