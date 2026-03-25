<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idcard_reader_settings', function (Blueprint $table) {
            $table->id();
            $table->string('ws_host', 255)->default('127.0.0.1');
            $table->unsignedInteger('ws_port')->default(14820);
            $table->boolean('auto_connect')->default(false);
            $table->boolean('auto_save')->default(false);
            $table->timestamps();
        });

        // Insert default row
        DB::table('idcard_reader_settings')->insert([
            'ws_host' => '127.0.0.1',
            'ws_port' => 14820,
            'auto_connect' => false,
            'auto_save' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('idcard_reader_settings');
    }
};
