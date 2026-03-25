<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocr_results', function (Blueprint $table) {
            $table->float('ocr_confidence')->nullable()->after('extracted_data')
                ->comment('Average OCR confidence score 0.0–1.0 from Google Vision API');
        });
    }

    public function down(): void
    {
        Schema::table('ocr_results', function (Blueprint $table) {
            $table->dropColumn('ocr_confidence');
        });
    }
};
