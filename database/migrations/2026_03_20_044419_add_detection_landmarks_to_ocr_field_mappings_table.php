<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocr_field_mappings', function (Blueprint $table) {
            $table->json('detection_landmarks')->nullable()->after('fields');
            /*
             * detection_landmarks JSON structure:
             * [
             *   { "type": "mrz",      "weight": 100 },
             *   { "type": "keyword",  "value": "PASSPORT",             "weight": 30 },
             *   { "type": "keyword",  "value": "Nationality",          "weight": 20 },
             *   { "type": "regex",    "value": "P[A-Z<][A-Z]{3}[A-Z<]+", "weight": 50 },
             * ]
             *
             * Types:
             *   mrz         — check if MRZ lines exist (no value needed)
             *   keyword     — case-insensitive substring match
             *   regex       — PCRE pattern match
             *   not_keyword — penalize if keyword found (negative scoring)
             */
        });
    }

    public function down(): void
    {
        Schema::table('ocr_field_mappings', function (Blueprint $table) {
            $table->dropColumn('detection_landmarks');
        });
    }
};
