<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ScanBatch;

class Labour extends Model
{
    protected $table = 'labours';

    protected $fillable = [
        'user_id',
        'batch_id',
        'visibility',   // 'private' | 'public'
        'id_card',
        'passport_no',
        'document_type',
        'prefix',
        'firstname',
        'lastname',
        'birthdate',
        'address',
        'nationality',
        'issue_date',
        'expiry_date',
        'photo',
    ];

    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ScanBatch::class, 'batch_id');
    }

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }
}
