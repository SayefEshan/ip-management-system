<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_email' => $this->user_email,
            'session_id' => $this->session_id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity_ip' => $this->entity_ip ?? null,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}