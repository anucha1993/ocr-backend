<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('note')->nullable();
            $table->unsignedInteger('total_count')->default(0);
            $table->timestamps();
        });

        Schema::table('labours', function (Blueprint $table) {
            $table->foreignId('batch_id')->nullable()->after('id')->constrained('scan_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_id');
        });

        Schema::dropIfExists('scan_batches');
    }
};
