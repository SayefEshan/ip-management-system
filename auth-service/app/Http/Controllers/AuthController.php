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
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Login failed'
            ], 500);
        }
    }
}
