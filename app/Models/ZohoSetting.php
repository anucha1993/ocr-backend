<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZohoSetting extends Model
{
    protected $fillable = [
        'client_id',
        'client_secret',
        'refresh_token',
        'api_domain',
    ];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'refresh_token' => 'encrypted',
        ];
    }
}
