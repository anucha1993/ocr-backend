<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            if (!Schema::hasColumn('labours', 'middlename')) {
                $table->string('middlename')->nullable()->after('firstname');
            }
            if (!Schema::hasColumn('labours', 'firstname_en')) {
                $table->string('firstname_en')->nullable()->after('lastname');
            }
            if (!Schema::hasColumn('labours', 'middlename_en')) {
                $table->string('middlename_en')->nullable()->after('firstname_en');
            }
            if (!Schema::hasColumn('labours', 'lastname_en')) {
                $table->string('lastname_en')->nullable()->after('middlename_en');
            }
            if (!Schema::hasColumn('labours', 'gender')) {
                $table->string('gender', 20)->nullable()->after('birthdate');
            }
            if (!Schema::hasColumn('labours', 'issue_place')) {
                $table->string('issue_place')->nullable()->after('issue_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('labours', function (Blueprint $table) {
            $table->dropColumn(['middlename', 'firstname_en', 'middlename_en', 'lastname_en', 'gender', 'issue_place']);
        });
    }
};
