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
        Schema::create('zoho_settings', function (Blueprint $table) {
            $table->id();
            $table->text('client_id');
            $table->text('client_secret');
            $table->text('refresh_token');
            $table->string('api_domain')->default('https://www.zohoapis.com');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoho_settings');
    }
};
