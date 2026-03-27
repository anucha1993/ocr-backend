<?php

namespace Database\Seeders;

use App\Models\OcrFieldMapping;
use App\Services\OcrParserService;
use Illuminate\Database\Seeder;

class OcrFieldMappingSeeder extends Seeder
{
    public function run(): void
    {
        OcrFieldMapping::updateOrCreate(
            ['name' => 'Passport (Default)'],
            [
                'fields'    => OcrParserService::defaultPassportFields(),
                'detection_landmarks' => [
                    ['type' => 'mrz',     'value' => null,           'weight' => 100],
                    ['type' => 'keyword', 'value' => 'PASSPORT',     'weight' => 30],
                    ['type' => 'keyword', 'value' => 'Passport No',  'weight' => 25],
                    ['type' => 'keyword', 'value' => 'Nationality',  'weight' => 15],
                    ['type' => 'keyword', 'value' => 'Date of birth','weight' => 15],
                    ['type' => 'keyword', 'value' => 'Date of expiry','weight' => 15],
                    ['type' => 'not_keyword', 'value' => 'เลขประจำตัวประชาชน', 'weight' => 50],
                ],
                'is_active' => true,
            ]
        );

        OcrFieldMapping::firstOrCreate(
            ['name' => 'ID Card (Thai)'],
            [
                'fields' => [
                    [
                        'key'             => 'id_card_number',
                        'label'           => 'ID Card Number',
                        'keywords'        => ['เลขประจำตัวประชาชน', 'Identification Number', 'ID No'],
                        'regex'           => '(\d[\s\-]?\d{4}[\s\-]?\d{5}[\s\-]?\d{2}[\s\-]?\d)',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'full_name_th',
                        'label'           => 'Full Name (Thai)',
                        'keywords'        => ['ชื่อ', 'ชื่อตัวและชื่อสกุล'],
                        'regex'           => null,
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'full_name_en',
                        'label'           => 'Full Name (English)',
                        'keywords'        => ['Name', 'Full Name'],
                        'regex'           => '(?:Name|Last\s*name)\s*([A-Za-z\s\-]+)',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'date_of_birth',
                        'label'           => 'Date of Birth',
                        'keywords'        => ['เกิดวันที่', 'Date of Birth', 'Birth'],
                        'regex'           => '(?:Date\s*of\s*Birth|เกิดวันที่)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'expiry_date',
                        'label'           => 'Expiry Date',
                        'keywords'        => ['บัตรหมดอายุ', 'Date of Expiry', 'Expiry'],
                        'regex'           => '(?:Date\s*of\s*Expiry|บัตรหมดอายุ)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'address',
                        'label'           => 'Address',
                        'keywords'        => ['ที่อยู่', 'Address'],
                        'regex'           => null,
                        'extraction_mode' => 'same_line',
                    ],
                ],
                'detection_landmarks' => [
                    ['type' => 'keyword', 'value' => 'เลขประจำตัวประชาชน', 'weight' => 80],
                    ['type' => 'keyword', 'value' => 'บัตรประจำตัวประชาชน', 'weight' => 60],
                    ['type' => 'keyword', 'value' => 'Thai National ID', 'weight' => 50],
                    ['type' => 'keyword', 'value' => 'Identification Number', 'weight' => 20],
                    ['type' => 'regex',   'value' => '\\d[\\s\\-]?\\d{4}[\\s\\-]?\\d{5}[\\s\\-]?\\d{2}[\\s\\-]?\\d', 'weight' => 40],
                    ['type' => 'not_keyword', 'value' => 'PASSPORT', 'weight' => 50],
                ],
                'is_active' => true,
            ]
        );
    }
}
