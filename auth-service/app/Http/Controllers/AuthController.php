<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActiveSession;
use App\Models\RefreshToken;
use App\Services\JWTService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class AuthController extends Controller
{
    private JWTService $jwtService;
    private AuditService $auditService;

    public function __construct(JWTService $jwtService, AuditService $auditService)
    {
        $this->jwtService = $jwtService;
        $this->auditService = $auditService;
    }

    public function login(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Find user
        $user = User::where('email', $request->email)->first();

        // Check credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->auditService->logFailedLogin($request->email, $request->ip());

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Check if user already logged in
        $existingSession = ActiveSession::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingSession) {
            return response()->json([
                'message' => 'User already logged in on another device'
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Generate session ID
            $sessionId = Str::uuid()->toString();

            // Generate tokens
            $accessToken = $this->jwtService->generateAccessToken(
                $user->toArray(),
                $sessionId
            );

            $refreshToken = $this->jwtService->generateRefreshToken(
                $user->id,
                $accessToken['jti']
            );

            // Save active session
            ActiveSession::create([
                'user_id' => $user->id,
                'token_jti' => $accessToken['jti'],
                'device_info' => $request->header('User-Agent'),
                'ip_address' => $request->ip(),
                'expires_at' => Carbon::now()->addSeconds($accessToken['expires_in'])
            ]);

            // Save refresh token
            RefreshToken::create([
                'user_id' => $user->id,
                'token_jti' => $refreshToken['jti'],
                'access_token_jti' => $accessToken['jti'],
                'expires_at' => Carbon::now()->addSeconds($refreshToken['expires_in'])
            ]);

            // Log successful login
            $this->auditService->logLogin($user->id, $sessionId, $request->ip());

            DB::commit();

            return response()->json([
                'access_token' => $accessToken['token'],
                'refresh_token' => $refreshToken['token'],
                'token_type' => 'Bearer',
                'expires_in' => $accessToken['expires_in'],
                'session_id' => $sessionId,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->is_super_admin
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Login failed'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'message' => 'Token not provided'
                ], 401);
            }

            // Validate and decode token
            $payload = $this->jwtService->validateToken($token);

            DB::beginTransaction();

            // Delete active session
            ActiveSession::where('user_id', $payload['sub'])
                ->where('token_jti', $payload['jti'])
                ->delete();

            // Revoke refresh tokens
            RefreshToken::where('user_id', $payload['sub'])
                ->where('access_token_jti', $payload['jti'])
                ->update(['revoked' => true]);

            // Log logout
            $this->auditService->logLogout(
                $payload['sub'],
                $payload['session_id'],
                $request->ip()
            );

            DB::commit();

            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string'
        ]);

        try {
            // Validate refresh token
            $payload = $this->jwtService->validateToken($request->refresh_token);

            // Check if it's a refresh token
            if ($payload['type'] !== 'refresh') {
                throw new Exception('Invalid token type');
            }

            // Find refresh token in database
            $refreshToken = RefreshToken::where('token_jti', $payload['jti'])
                ->where('revoked', false)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$refreshToken) {
                throw new Exception('Invalid or expired refresh token');
            }

            // Get user
            $user = User::find($refreshToken->user_id);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Get active session
            $activeSession = ActiveSession::where('user_id', $user->id)
                ->where('token_jti', $refreshToken->access_token_jti)
                ->first();

            if (!$activeSession) {
                throw new Exception('Session not found');
            }

            DB::beginTransaction();

            // Generate new access token
            $sessionId = $request->header('X-Session-ID');
            $newAccessToken = $this->jwtService->generateAccessToken(
                $user->toArray(),
                $sessionId
            );

            // Update active session with new token
            $activeSession->update([
                'token_jti' => $newAccessToken['jti'],
                'expires_at' => Carbon::now()->addSeconds($newAccessToken['expires_in'])
            ]);

            // Update refresh token with new access token JTI
            $refreshToken->update([
                'access_token_jti' => $newAccessToken['jti']
            ]);

            DB::commit();

            return response()->json([
                'access_token' => $newAccessToken['token'],
                'token_type' => 'Bearer',
                'expires_in' => $newAccessToken['expires_in']
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function validateToken(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                throw new Exception('Token not provided');
            }

            // Validate and decode token
            $payload = $this->jwtService->validateToken($token);

            // Check if it's an access token
            if ($payload['type'] !== 'access') {
                throw new Exception('Invalid token type');
            }

            // Check if session is still active
            $activeSession = ActiveSession::where('user_id', $payload['sub'])
                ->where('token_jti', $payload['jti'])
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$activeSession) {
                throw new Exception('Session not found or expired');
            }

            // Get user
            $user = User::find($payload['sub']);
            if (!$user) {
                throw new Exception('User not found');
            }

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'is_super_admin' => $user->is_super_admin
                ],
                'session_id' => $payload['session_id']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
}
