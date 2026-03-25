<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiEndpoint extends Model
{
    protected $fillable = [
        'provider_id',
        'name',
        'method',
        'endpoint',
        'description',
        'default_headers',
        'default_body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_headers' => 'array',
            'default_body' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ApiProvider::class, 'provider_id');
    }

    public function getFullUrl(): string
    {
        return rtrim($this->provider->base_url, '/') . '/' . ltrim($this->endpoint, '/');
    }
}
