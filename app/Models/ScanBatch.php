<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanBatch extends Model
{
    protected $table = 'scan_batches';

    protected $fillable = [
        'user_id',
        'name',
        'label',
        'note',
        'total_count',
        'visibility',   // 'private' | 'public'
    ];

    protected $attributes = [
        'visibility' => 'private',
    ];

    public function labours(): HasMany
    {
        return $this->hasMany(Labour::class, 'batch_id');
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
