<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                // e.g. "Thai Passport", "Myanmar Passport"
            $table->string('doc_type_code', 10);         // e.g. "P", "PJ" — first field
            $table->string('country_code', 10);          // e.g. "THA", "MMR" — second field
            $table->json('field_map');                    // JSON: { "0": "doc_type", "1": "country", ... }
            $table->string('date_format', 20)->default('YYMMDD'); // date format in raw data
            $table->string('separator', 5)->default('#'); // field separator
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['doc_type_code', 'country_code']);
        });

        // Seed default mappings
        DB::table('passport_mappings')->insert([
            [
                'name' => 'Thai Passport',
                'doc_type_code' => 'P',
                'country_code' => 'THA',
                'field_map' => json_encode([
                    ['index' => 0, 'field' => 'doc_type'],
                    ['index' => 1, 'field' => 'issuing_country'],
                    ['index' => 2, 'field' => 'firstname'],
                    ['index' => 3, 'field' => 'lastname'],
                    ['index' => 4, 'field' => 'passport_no'],
                    ['index' => 5, 'field' => 'nationality'],
                    ['index' => 6, 'field' => 'birthdate'],
                    ['index' => 7, 'field' => 'gender'],
                    ['index' => 8, 'field' => 'expiry_date'],
                    ['index' => 9, 'field' => 'personal_no'],
                ]),
                'date_format' => 'YYMMDD',
                'separator' => '#',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Myanmar Passport',
                'doc_type_code' => 'PJ',
                'country_code' => 'MMR',
                'field_map' => json_encode([
                    ['index' => 0, 'field' => 'doc_type'],
                    ['index' => 1, 'field' => 'issuing_country'],
                    ['index' => 2, 'field' => 'firstname'],
                    ['index' => 3, 'field' => 'lastname'],
                    ['index' => 4, 'field' => 'passport_no'],
                    ['index' => 5, 'field' => 'nationality'],
                    ['index' => 6, 'field' => 'birthdate'],
                    ['index' => 7, 'field' => 'gender'],
                    ['index' => 8, 'field' => 'expiry_date'],
                ]),
                'date_format' => 'YYMMDD',
                'separator' => '#',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_mappings');
    }
};
