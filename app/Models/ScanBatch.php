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
        'note',
        'total_count',
    ];

    public function labours(): HasMany
    {
        return $this->hasMany(Labour::class, 'batch_id');
    }
}
