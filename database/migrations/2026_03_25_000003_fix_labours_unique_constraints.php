<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            // Drop the old single-column unique indexes
            $table->dropUnique(['id_card']);
            $table->dropUnique(['passport_no']);
            // Add composite unique indexes scoped to each user
            $table->unique(['user_id', 'id_card']);
            $table->unique(['user_id', 'passport_no']);
        });
    }

    public function down(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'id_card']);
            $table->dropUnique(['user_id', 'passport_no']);
            $table->unique(['id_card']);
            $table->unique(['passport_no']);
        });
    }
};
