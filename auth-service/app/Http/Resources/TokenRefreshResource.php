<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenRefreshResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $this->resource['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => $this->resource['expires_in'],
            ]
        ];
    }
}