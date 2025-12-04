<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware for role-based access control.
 * 
 * This middleware verifies that the authenticated user has one of the
 * allowed roles before granting access to a route or group of routes.
 * 
 * @package App\Http\Middleware
 * @author EDULIFE Team
 * 
 * @example
 * // Single role
 * Route::middleware('role:admin')->group(function () {
 *     // Only admins can access
 * });
 * 
 * // Multiple roles
 * Route::middleware('role:admin,super_admin')->group(function () {
 *     // Admins and super admins can access
 * });
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Checks if the authenticated user's role matches one of the allowed roles.
     * Redirects to login if not authenticated, or returns 403 if unauthorized.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the chain
     * @param string ...$roles Variadic list of allowed role names
     * 
     * @return Response
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 403 if user lacks required role
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Get the authenticated user
        $user = auth()->user();

        // Check if user's role is in allowed roles
        if (!in_array($user->role, $roles)) {
            abort(403, 'Sizda bu sahifaga kirish huquqi yo\'q.');
        }

        return $next($request);
    }
}
