<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActiveSession;
use App\Models\RefreshToken;
use App\Services\JWTService;
use App\Services\AuditService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\TokenRefreshResource;
use App\Http\Resources\TokenValidationResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\ApiResponse;
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

    public function login(LoginRequest $request)
    {
        

        $validated = $request->validated();

        // Find user
        $user = User::where('email', $validated['email'])->first();

        // Check credentials
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            $this->auditService->logFailedLogin($request->email, $request->ip());

            return ApiResponse::error('Invalid credentials', null, 401);
        }

        // Check if user already logged in
        $existingSession = ActiveSession::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingSession) {
            return ApiResponse::error('User already logged in on another device', null, 403);
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
            $this->auditService->logLogin($user->id, $user->email, $sessionId, $request->ip());

            DB::commit();

            return new LoginResource([
                'access_token' => $accessToken['token'],
                'refresh_token' => $refreshToken['token'],
                'expires_in' => $accessToken['expires_in'],
                'session_id' => $sessionId,
                'user' => $user
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Login failed', null, 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return ApiResponse::error('Token not provided', null, 401);
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

            // Get user email
            $user = User::find($payload['sub']);

            // Log logout
            $this->auditService->logLogout(
                $payload['sub'],
                $payload['session_id'],
                $request->ip(),
                $user->email
            );

            DB::commit();

            return ApiResponse::success(null, 'Successfully logged out');
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Logout failed', ['error' => $e->getMessage()], 400);
        }
    }

    public function refresh(RefreshTokenRequest $request)
    {
        

        try {
            $validated = $request->validated();
            // Validate refresh token
            $payload = $this->jwtService->validateToken($validated['refresh_token']);

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

            return new TokenRefreshResource([
                'access_token' => $newAccessToken['token'],
                'expires_in' => $newAccessToken['expires_in']
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::error('Token refresh failed', ['error' => $e->getMessage()], 401);
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

            return new TokenValidationResource([
                'valid' => true,
                'user' => $user,
                'session_id' => $payload['session_id']
            ]);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), ['valid' => false], 401);
        }
    }
}
