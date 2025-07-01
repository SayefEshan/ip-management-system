<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $this->resource['access_token'],
                'refresh_token' => $this->resource['refresh_token'],
                'token_type' => 'Bearer',
                'expires_in' => $this->resource['expires_in'],
                'session_id' => $this->resource['session_id'],
                'user' => new UserResource($this->resource['user']),
            ]
        ];
    }
}