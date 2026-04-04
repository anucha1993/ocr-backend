<?php

namespace Database\Seeders;

use App\Models\DocumentTypeRule;
use Illuminate\Database\Seeder;

class DocumentTypeRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['document_type' => 'PW',  'label' => 'PW (Work Permit)',       'validity_years' => 2,  'offset_days' => 0],
            ['document_type' => 'PJ',  'label' => 'PJ (Passport for Job)',  'validity_years' => 5,  'offset_days' => 0],
            ['document_type' => 'P',   'label' => 'P (Passport)',           'validity_years' => 5,  'offset_days' => 0],
            ['document_type' => 'PN',  'label' => 'PN (Passport National)', 'validity_years' => 10, 'offset_days' => 0],
            ['document_type' => 'CI',  'label' => 'CI (Certificate of ID)', 'validity_years' => 5,  'offset_days' => 0],
            ['document_type' => 'WP',  'label' => 'Work Permit',            'validity_years' => 2,  'offset_days' => 0],
            ['document_type' => 'PINK','label' => 'บัตรประชมพู (Pink Card)', 'validity_years' => 4,  'offset_days' => 0],
        ];

        foreach ($rules as $rule) {
            DocumentTypeRule::updateOrCreate(
                ['document_type' => $rule['document_type']],
                $rule
            );
        }
    }
}
