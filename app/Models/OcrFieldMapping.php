<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OcrFieldMapping extends Model
{
    protected $fillable = [
        'name',
        'fields',
        'detection_landmarks',
        'is_active',
    ];

    protected $casts = [
        'fields'              => 'array',
        'detection_landmarks' => 'array',
        'is_active'           => 'boolean',
    ];

    public function ocrResults(): HasMany
    {
        return $this->hasMany(OcrResult::class, 'field_mapping_id');
    }
}
