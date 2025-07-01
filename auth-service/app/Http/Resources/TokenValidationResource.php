<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenValidationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'valid' => $this->resource['valid'],
                'user' => new UserResource($this->resource['user']),
                'session_id' => $this->resource['session_id'],
            ]
        ];
    }
}