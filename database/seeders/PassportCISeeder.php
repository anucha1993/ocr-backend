<?php

namespace Database\Seeders;

use App\Models\OcrFieldMapping;
use Illuminate\Database\Seeder;

class PassportCISeeder extends Seeder
{
    public function run(): void
    {
        OcrFieldMapping::updateOrCreate(
            ['name' => 'Passport CI (Myanmar)'],
            [
                'fields' => [
                    [
                        'key'             => 'type',
                        'label'           => 'Type',
                        'keywords'        => [],
                        'regex'           => 'type',
                        'extraction_mode' => 'mrz',
                        'value_map'       => ['Cl' => 'CI', 'C1' => 'CI'],
                    ],
                    [
                        'key'             => 'country_code',
                        'label'           => 'Country Code',
                        'keywords'        => [],
                        'regex'           => 'country',
                        'extraction_mode' => 'mrz',
                    ],
                    [
                        'key'             => 'coi_number',
                        'label'           => 'COI No.',
                        'keywords'        => [],
                        'regex'           => 'document_number',
                        'extraction_mode' => 'mrz',
                    ],
                    [
                        'key'             => 'full_name',
                        'label'           => 'Full Name',
                        'keywords'        => [],
                        'regex'           => 'full_name',
                        'extraction_mode' => 'mrz',
                    ],
                    [
                        'key'             => 'also_known_as',
                        'label'           => 'Also Known As',
                        'keywords'        => [],
                        'regex'           => 'Also known as\s+([A-Z][A-Z ]{2,40})',
                        'extraction_mode' => 'auto',
                    ],
                    [
                        'key'             => 'nationality',
                        'label'           => 'Nationality',
                        'keywords'        => [],
                        'regex'           => 'nationality',
                        'extraction_mode' => 'mrz',
                        'transform'       => ['normalize_nationality'],
                    ],
                    [
                        'key'             => 'date_of_birth',
                        'label'           => 'Date of Birth',
                        'keywords'        => [],
                        'regex'           => 'date_of_birth',
                        'extraction_mode' => 'mrz',
                    ],
                    [
                        'key'             => 'sex',
                        'label'           => 'Sex',
                        'keywords'        => [],
                        'regex'           => 'sex',
                        'extraction_mode' => 'mrz',
                        'transform'       => ['normalize_gender'],
                    ],
                    [
                        'key'             => 'place_of_birth',
                        'label'           => 'Place of Birth',
                        'keywords'        => [],
                        'regex'           => 'Place of birth\s+([A-Z][A-Za-z ,]+)',
                        'extraction_mode' => 'auto',
                    ],
                    [
                        'key'             => 'authority',
                        'label'           => 'Authority',
                        'keywords'        => ['Authority'],
                        'regex'           => null,
                        'extraction_mode' => 'next_line',
                    ],
                    [
                        'key'             => 'date_of_issue',
                        'label'           => 'Date of Issue',
                        'keywords'        => [],
                        'regex'           => 'Date of issue\s+(\d{1,2}\s+[A-Z]{3,9}\s+\d{4})',
                        'extraction_mode' => 'auto',
                    ],
                    [
                        'key'             => 'date_of_expiry',
                        'label'           => 'Date of Expiry',
                        'keywords'        => [],
                        'regex'           => 'date_of_expiry',
                        'extraction_mode' => 'mrz',
                    ],
                ],
                'detection_landmarks' => [
                    ['type' => 'mrz',         'value' => null,                                'weight' => 80],
                    ['type' => 'regex',       'value' => 'CIMMR|CI[A-Z]{3}',                  'weight' => 100],
                    ['type' => 'keyword',     'value' => 'REPUBLIC OF THE UNION OF MYANMAR',  'weight' => 60],
                    ['type' => 'keyword',     'value' => 'COI No',                            'weight' => 40],
                    ['type' => 'regex',       'value' => '(?:COI|CO!|CO1)\\s*(?:No|no)',       'weight' => 30],
                    ['type' => 'keyword',     'value' => 'Also known as',                     'weight' => 20],
                    ['type' => 'not_keyword', 'value' => 'NON THAI IDENTIFICATION CARD',      'weight' => 50],
                    ['type' => 'not_keyword', 'value' => 'เลขประจำตัวประชาชน',                  'weight' => 50],
                ],
                'is_active' => true,
            ]
        );
    }
}
