<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'token_url',
        'client_id',
        'client_secret',
        'refresh_token',
        'access_token',
        'token_expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function endpoints(): HasMany
    {
        return $this->hasMany(ApiEndpoint::class, 'provider_id');
    }

    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return true;
        }

        return $this->token_expires_at->isPast();
    }
}
