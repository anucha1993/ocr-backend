<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->string('id_card', 20)->nullable()->change();
            $table->string('passport_no', 20)->nullable()->unique()->after('id_card');
            $table->string('nationality', 100)->nullable()->after('address');
            $table->string('document_type', 20)->default('idcard')->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->string('id_card', 20)->nullable(false)->change();
            $table->dropColumn(['passport_no', 'nationality', 'document_type']);
        });
    }
};
