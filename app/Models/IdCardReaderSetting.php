<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdCardReaderSetting extends Model
{
    protected $table = 'idcard_reader_settings';

    protected $fillable = [
        'ws_host',
        'ws_port',
        'auto_connect',
        'auto_save',
    ];

    protected function casts(): array
    {
        return [
            'ws_port' => 'integer',
            'auto_connect' => 'boolean',
            'auto_save' => 'boolean',
        ];
    }

    /**
     * Get the single settings row (singleton pattern).
     */
    public static function current(): self
    {
        return self::firstOrCreate([], [
            'ws_host' => '127.0.0.1',
            'ws_port' => 14820,
            'auto_connect' => false,
            'auto_save' => false,
        ]);
    }
}
