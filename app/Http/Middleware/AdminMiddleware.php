<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect('admin/login');
        }

        $user = Auth::user();

        // Check if user exists and has active status
        if (! $user || ! isset($user->status) || $user->status != 1) {
            // For AJAX requests, return JSON response
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. Account is not active.'], 403);
            }

            return redirect('admin/login')->with('error', '您的账户已被禁用或未激活');
        }

        return $next($request);
    }
}
