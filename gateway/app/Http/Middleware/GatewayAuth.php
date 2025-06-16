<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class GatewayAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Token not provided'
            ], 401);
        }

        try {
            // Validate token with auth service
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'X-Session-ID' => $request->header('X-Session-ID'),
            ])->get(env('AUTH_SERVICE_URL') . '/api/auth/validate');

            if ($response->successful()) {
                $userData = $response->json();

                $request->headers->set('X-User-Context', json_encode([
                    'id' => $userData['user']['id'],
                    'email' => $userData['user']['email'],
                    'is_super_admin' => $userData['user']['is_super_admin'],
                    'session_id' => $userData['session_id'],
                ]));

                return $next($request);
            }

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Service unavailable',
                'message' => 'Unable to validate authentication'
            ], 503);
        }
    }
}
