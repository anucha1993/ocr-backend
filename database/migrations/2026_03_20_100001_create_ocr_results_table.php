<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_results', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();       // groups files from same upload
            $table->string('original_filename');
            $table->string('file_type');                // pdf, jpg, png, etc.
            $table->integer('page_count')->default(1);
            $table->longText('raw_text')->nullable();   // full OCR text
            $table->json('extracted_data')->nullable();  // parsed key-value fields
            $table->foreignId('field_mapping_id')->nullable()->constrained('ocr_field_mappings')->nullOnDelete();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_results');
    }
};
