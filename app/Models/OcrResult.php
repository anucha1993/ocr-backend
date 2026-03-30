<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrResult extends Model
{
    protected $fillable = [
        'batch_id',
        'original_filename',
        'file_type',
        'page_count',
        'page_number',
        'raw_text',
        'extracted_data',
        'ocr_confidence',
        'validation',
        'field_mapping_id',
        'status',
        'error_message',
        'user_id',
    ];

    protected $casts = [
        'extracted_data'  => 'array',
        'validation'      => 'array',
        'page_count'      => 'integer',
        'page_number'     => 'integer',
        'ocr_confidence'  => 'float',
    ];

    public function fieldMapping(): BelongsTo
    {
        return $this->belongsTo(OcrFieldMapping::class, 'field_mapping_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
