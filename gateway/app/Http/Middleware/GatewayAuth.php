<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
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
        $refreshToken = $request->header('X-Refresh-Token');

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
            ])->get(config('app.services.auth.url') . '/api/auth/validate');

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

            // Token expired - try to refresh if refresh token provided
            if ($response->status() === 401 && $refreshToken) {

                // Refresh the token
                $refreshResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'X-Session-ID' => $request->header('X-Session-ID'),
                ])->post(config('app.services.auth.url') . '/api/auth/refresh', [
                    'refresh_token' => $refreshToken
                ]);

                if ($refreshResponse->successful()) {
                    $newToken = $refreshResponse->json('access_token');

                    // Update request with new token
                    $request->headers->set('Authorization', 'Bearer ' . $newToken);

                    // Validate new token and get user context
                    $validateResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $newToken,
                        'X-Session-ID' => $request->header('X-Session-ID'),
                    ])->get(config('app.services.auth.url') . '/api/auth/validate');

                    if ($validateResponse->successful()) {
                        $userData = $validateResponse->json();
                        $request->headers->set('X-User-Context', json_encode([
                            'id' => $userData['user']['id'],
                            'email' => $userData['user']['email'],
                            'is_super_admin' => $userData['user']['is_super_admin'],
                            'session_id' => $userData['session_id'],
                        ]));

                        // Process request with new token
                        $response = $next($request);

                        // Send new token back to client
                        $response->headers->set('X-New-Access-Token', $newToken);

                        return $response;
                    }
                }
            }

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid token'
            ], 401);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Service unavailable',
                'message' => 'Unable to validate authentication'
            ], 503);
        }
    }
}
