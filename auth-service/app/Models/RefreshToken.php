<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_jti',
        'access_token_jti',
        'expires_at',
        'revoked',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include valid tokens.
     */
    public function scopeValid($query)
    {
        return $query->where('revoked', false)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Check if the token is valid.
     */
    public function isValid()
    {
        return !$this->revoked && $this->expires_at->isFuture();
    }

    /**
     * Revoke the token.
     */
    public function revoke()
    {
        $this->update(['revoked' => true]);
    }
}
