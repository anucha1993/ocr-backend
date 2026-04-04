<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_rules', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->unique();   // PW, PJ, P, CI, etc.
            $table->string('label', 100);                     // display name
            $table->integer('validity_years')->default(5);    // document validity in years
            $table->integer('offset_days')->default(0);       // expiry offset from issue anniversary: 0 = same day, -1 = minus 1 day
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_rules');
    }
};
