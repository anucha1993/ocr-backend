<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_field_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // e.g. "Passport Fields", "ID Card Fields"
            $table->json('fields');                     // array of field definitions
            /*
             * fields JSON structure:
             * [
             *   {
             *     "key": "full_name",
             *     "label": "Full Name",
             *     "keywords": ["Name:", "Full Name:", "Name", "ชื่อ"],
             *     "regex": "(?:Name|Full\\s*Name)\\s*[:：]?\\s*(.+)"
             *   },
             *   ...
             * ]
             */
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_field_mappings');
    }
};
