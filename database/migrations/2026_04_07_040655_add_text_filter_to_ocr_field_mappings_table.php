<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ocr_field_mappings', function (Blueprint $table) {
            $table->string('text_start_after', 255)->nullable()->after('detection_landmarks');
            $table->string('text_end_before', 255)->nullable()->after('text_start_after');
        });
    }

    public function down(): void
    {
        Schema::table('ocr_field_mappings', function (Blueprint $table) {
            $table->dropColumn(['text_start_after', 'text_end_before']);
        });
    }
};
