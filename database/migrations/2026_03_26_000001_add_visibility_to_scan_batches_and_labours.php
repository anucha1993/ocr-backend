<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // scan_batches: visibility + label for grouping
        Schema::table('scan_batches', function (Blueprint $table) {
            $table->string('label')->nullable()->after('name');         // ป้ายกำกับกลุ่ม
            $table->enum('visibility', ['private', 'public'])->default('private')->after('note');
        });

        // labours: visibility per record
        Schema::table('labours', function (Blueprint $table) {
            $table->enum('visibility', ['private', 'public'])->default('private')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('scan_batches', function (Blueprint $table) {
            $table->dropColumn(['label', 'visibility']);
        });
        Schema::table('labours', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
