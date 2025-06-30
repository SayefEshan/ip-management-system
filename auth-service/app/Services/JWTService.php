<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Str;

class JWTService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('app.jwt.secret');

        if (!$this->secretKey) {
            throw new Exception('JWT secret key not configured');
        }
    }

    public function generateAccessToken(array $user, string $sessionId)
    {
        $jti = Str::uuid()->toString();

        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'is_super_admin' => $user['is_super_admin'],
            'session_id' => $sessionId,
            'type' => 'access',
            'jti' => $jti,
            'iat' => time(),
            'exp' => time() + 3600  // 1 hour
        ];

        $token = $this->createToken($payload);

        return [
            'token' => $token,
            'expires_in' => 3600,
            'jti' => $jti
        ];
    }

    public function generateRefreshToken(int $userId, string $accessJti)
    {
        $jti = Str::uuid()->toString();

        $payload = [
            'sub' => $userId,
            'access_jti' => $accessJti,
            'type' => 'refresh',
            'jti' => $jti,
            'iat' => time(),
            'exp' => time() + 604800  // 7 days
        ];

        $token = $this->createToken($payload);

        return [
            'token' => $token,
            'expires_in' => 604800,
            'jti' => $jti
        ];
    }

    public function validateToken(string $token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        // Verify signature
        $expectedSignature = $this->createSignature($header . '.' . $payload);

        if ($signature !== $expectedSignature) {
            throw new Exception('Invalid signature');
        }

        // Decode payload
        $data = json_decode(base64_decode($payload), true);

        if (!$data) {
            throw new Exception('Invalid payload');
        }

        // Check if expired
        if ($data['exp'] < time()) {
            throw new Exception('Token expired');
        }

        return $data;
    }

    private function createToken(array $payload)
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $headerEncoded = base64_encode(json_encode($header));
        $payloadEncoded = base64_encode(json_encode($payload));

        $signature = $this->createSignature($headerEncoded . '.' . $payloadEncoded);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    private function createSignature(string $data)
    {
        return base64_encode(
            hash_hmac('sha256', $data, $this->secretKey, true)
        );
    }
}
