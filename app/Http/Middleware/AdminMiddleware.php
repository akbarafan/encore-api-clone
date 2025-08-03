<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if request expects JSON (API request)
        if ($request->expectsJson()) {
            try {
                // Validate JWT token
                $user = JWTAuth::parseToken()->authenticate();
                
                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found'
                    ], 401);
                }

                // Check if user is admin (role = 1)
                if ($user->role != 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. Admin role required.'
                    ], 403);
                }

                // Set authenticated user
                auth()->setUser($user);

            } catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalid or expired'
                ], 401);
            }
        } else {
            // Web request - use session authentication
            if (!auth()->check()) {
                return redirect()->route('login')
                    ->with('error', 'Please login to access admin panel.');
            }

            // Check if user is admin
            if (auth()->user()->role != 1) {
                auth()->logout();
                return redirect()->route('login')
                    ->with('error', 'Access denied. Admin privileges required.');
            }
        }

        return $next($request);
    }
}
