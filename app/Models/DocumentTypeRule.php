<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentTypeRule extends Model
{
    protected $fillable = [
        'document_type',
        'label',
        'validity_years',
        'offset_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'validity_years' => 'integer',
            'offset_days'    => 'integer',
            'is_active'      => 'boolean',
        ];
    }
}
