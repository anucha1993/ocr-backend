<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('api_providers')->cascadeOnDelete();
            $table->string('name');
            $table->string('method')->default('GET');
            $table->string('endpoint');
            $table->text('description')->nullable();
            $table->json('default_headers')->nullable();
            $table->json('default_body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
