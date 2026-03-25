<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportMapping extends Model
{
    protected $table = 'passport_mappings';

    protected $fillable = [
        'name',
        'doc_type_code',
        'country_code',
        'field_map',
        'date_format',
        'separator',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'field_map' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
