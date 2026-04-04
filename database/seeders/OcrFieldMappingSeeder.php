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
                        'labour_field'    => 'id_card',
                    ],
                    [
                        'key'             => 'full_name_th',
                        'label'           => 'Full Name (Thai)',
                        'keywords'        => ['ชื่อ', 'ชื่อตัวและชื่อสกุล'],
                        'regex'           => null,
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'firstname',
                    ],
                    [
                        'key'             => 'full_name_en',
                        'label'           => 'Full Name (English)',
                        'keywords'        => ['Name', 'Full Name'],
                        'regex'           => '(?:Name|Last\s*name)\s*([A-Za-z\s\-]+)',
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'firstname_en',
                    ],
                    [
                        'key'             => 'date_of_birth',
                        'label'           => 'Date of Birth',
                        'keywords'        => ['เกิดวันที่', 'Date of Birth', 'Birth'],
                        'regex'           => '(?:Date\s*of\s*Birth|เกิดวันที่)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'birthdate',
                    ],
                    [
                        'key'             => 'expiry_date',
                        'label'           => 'Expiry Date',
                        'keywords'        => ['บัตรหมดอายุ', 'Date of Expiry', 'Expiry'],
                        'regex'           => '(?:Date\s*of\s*Expiry|บัตรหมดอายุ)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'expiry_date',
                    ],
                    [
                        'key'             => 'address',
                        'label'           => 'Address',
                        'keywords'        => ['ที่อยู่', 'Address'],
                        'regex'           => null,
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'address',
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

        // Non-Thai Identification Card (บัตรประจำตัวคนซึ่งไม่มีสัญชาติไทย)
        OcrFieldMapping::updateOrCreate(
            ['name' => 'Non-Thai ID Card'],
            [
                'fields' => [
                    [
                        'key'             => 'card_number',
                        'label'           => 'Card Number (เลขที่บัตร)',
                        'keywords'        => [],
                        'regex'           => '(\d{2}\s*\d{4}\s*\d{5,7}\s*\d?)',
                        'extraction_mode' => 'auto',
                        'labour_field'    => 'id_card',
                    ],
                    [
                        'key'             => 'side_number',
                        'label'           => 'Side Number (เลขข้างบัตร)',
                        'keywords'        => [],
                        'regex'           => '(\d{4}[\-\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2015}]\d{7})',
                        'extraction_mode' => 'auto',
                        'labour_field'    => '',
                    ],
                    [
                        'key'             => 'nationality',
                        'label'           => 'Nationality (สัญชาติ)',
                        'keywords'        => ['NON THAI IDENTIFICATION CARD', 'ไม่มีสัญชาติไทย'],
                        'regex'           => null,
                        'extraction_mode' => 'next_line',
                        'labour_field'    => 'nationality',
                    ],
                    [
                        'key'             => 'name_th',
                        'label'           => 'Name Thai (ชื่อ)',
                        'keywords'        => ['ชื่อ'],
                        'regex'           => null,
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'firstname',
                    ],
                    [
                        'key'             => 'name_en',
                        'label'           => 'Name English',
                        'keywords'        => ['Name'],
                        'regex'           => 'Name\s+(.+)',
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'firstname_en',
                    ],
                    [
                        'key'             => 'date_of_birth',
                        'label'           => 'Date of Birth (เกิดวันที่)',
                        'keywords'        => ['Date of Birth', 'เกิดวันที่', 'เกิดวันที'],
                        'regex'           => 'Date of Birth\s+(\d{1,2}\s+\w+\.?\s+\d{4})',
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'birthdate',
                    ],
                    [
                        'key'             => 'address',
                        'label'           => 'Address (ที่อยู่)',
                        'keywords'        => ['ที่อยู่'],
                        'regex'           => null,
                        'extraction_mode' => 'same_line',
                        'labour_field'    => 'address',
                    ],
                    [
                        'key'             => 'date_of_issue',
                        'label'           => 'Date of Issue (วันออกบัตร)',
                        'keywords'        => ['วันออกบัตร'],
                        'regex'           => null,
                        'extraction_mode' => 'next_line',
                        'labour_field'    => 'issue_date',
                    ],
                    [
                        'key'             => 'date_of_expiry',
                        'label'           => 'Date of Expiry (วันหมดอายุ)',
                        'keywords'        => ['Date of Expiry', 'วันบัตรหมดอายุ'],
                        'regex'           => null,
                        'extraction_mode' => 'prev_line',
                        'labour_field'    => 'expiry_date',
                    ],
                ],
                'detection_landmarks' => [
                    ['type' => 'keyword',     'value' => 'NON THAI IDENTIFICATION CARD', 'weight' => 100],
                    ['type' => 'keyword',     'value' => 'บัตรประจำตัวคนซึ่งไม่มีสัญชาติไทย', 'weight' => 100],
                    ['type' => 'keyword',     'value' => 'วันออกบัตร',  'weight' => 15],
                    ['type' => 'keyword',     'value' => 'เกิดวันที',   'weight' => 15],
                    ['type' => 'not_keyword', 'value' => 'PASSPORT',    'weight' => 50],
                    ['type' => 'not_keyword', 'value' => 'เลขประจำตัวประชาชน', 'weight' => 50],
                ],
                'is_active' => true,
            ]
        );

        // Passport CI — Certificate of Identity (Myanmar)
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
                        'labour_field'    => 'document_type',
                        'value_map'       => ['Cl' => 'CI', 'C1' => 'CI'],
                    ],
                    [
                        'key'             => 'country_code',
                        'label'           => 'Country Code',
                        'keywords'        => [],
                        'regex'           => 'country',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => '',
                    ],
                    [
                        'key'             => 'coi_number',
                        'label'           => 'COI No.',
                        'keywords'        => [],
                        'regex'           => 'document_number',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => 'passport_no',
                    ],
                    [
                        'key'             => 'full_name',
                        'label'           => 'Full Name',
                        'keywords'        => [],
                        'regex'           => 'full_name',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => 'firstname',
                    ],
                    [
                        'key'             => 'also_known_as',
                        'label'           => 'Also Known As',
                        'keywords'        => [],
                        'regex'           => 'Also known as\s+([A-Z][A-Z ]{2,40})',
                        'extraction_mode' => 'auto',
                        'labour_field'    => '',
                    ],
                    [
                        'key'             => 'nationality',
                        'label'           => 'Nationality',
                        'keywords'        => [],
                        'regex'           => 'nationality',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => 'nationality',
                        'transform'       => ['normalize_nationality'],
                    ],
                    [
                        'key'             => 'date_of_birth',
                        'label'           => 'Date of Birth',
                        'keywords'        => [],
                        'regex'           => 'date_of_birth',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => 'birthdate',
                    ],
                    [
                        'key'             => 'sex',
                        'label'           => 'Sex',
                        'keywords'        => [],
                        'regex'           => 'sex',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => 'gender',
                        'transform'       => ['normalize_gender'],
                    ],
                    [
                        'key'             => 'place_of_birth',
                        'label'           => 'Place of Birth',
                        'keywords'        => [],
                        'regex'           => 'Place of birth\s+([A-Z][A-Za-z ,]+)',
                        'extraction_mode' => 'auto',
                        'labour_field'    => '',
                    ],
                    [
                        'key'             => 'authority',
                        'label'           => 'Authority',
                        'keywords'        => ['Authority'],
                        'regex'           => null,
                        'extraction_mode' => 'next_line',
                        'labour_field'    => 'issue_place',
                    ],
                    [
                        'key'             => 'date_of_issue',
                        'label'           => 'Date of Issue',
                        'keywords'        => [],
                        'regex'           => 'Date of issue\s+(\d{1,2}\s+[A-Z]{3,9}\s+\d{4})',
                        'extraction_mode' => 'auto',
                        'labour_field'    => 'issue_date',
                    ],
                    [
                        'key'             => 'date_of_expiry',
                        'label'           => 'Date of Expiry',
                        'keywords'        => [],
                        'regex'           => 'date_of_expiry',
                        'extraction_mode' => 'mrz',
                        'labour_field'    => 'expiry_date',
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
