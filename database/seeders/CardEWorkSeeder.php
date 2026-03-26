<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OcrFieldMapping;

class CardEWorkSeeder extends Seeder
{
    public function run(): void
    {
        OcrFieldMapping::updateOrCreate(
            ['name' => 'Card-E-WORK'],
            [
                'fields' => [
                    [
                        'key'             => 'work_permit_no',
                        'label'           => 'Work Permit No.',
                        'keywords'        => ['ใบอนุญาตทำงานเลขที่', 'ใบอนุญาตทํางานเลขที่', 'Work Permit No', 'Work Permit No.'],
                        'regex'           => '(?:Work\\s*Permit\\s*No\\.?|ใบอนุญาตท(?:ำ|ํา)งานเลขที่)\\s*[:：]?\\s*([\\d\\s]{10,30})',
                        'extraction_mode' => 'same_line',
                        'transform'       => ['remove_spaces'],
                    ],
                    [
                        'key'             => 'full_name',
                        'label'           => 'Full Name',
                        'keywords'        => ['ชื่อผู้รับอนุญาตให้ทำงาน', 'ชื่อผู้รับอนุญาตให้ทํางาน', 'Name'],
                        'regex'           => '(?:ชื่อผู้รับอนุญาตให้ท(?:ำ|ํา)งาน|Name)\\s*[:：]?\\s*([A-Z][A-Za-z .\\-]{2,60})',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'date_of_birth',
                        'label'           => 'Date of Birth',
                        'keywords'        => ['วัน เดือน ปีเกิด', 'วันเดือนปีเกิด', 'Date of Birth', 'DOB'],
                        'regex'           => '(?:Date\\s*of\\s*Birth|วัน\\s*เดือน\\s*ปีเกิด)\\s*[:：]?\\s*(\\d{1,2}\\s+[A-Za-z]{3,9}\\s+\\d{4})',
                        'extraction_mode' => 'same_line',                        'format'          => 'date:DD/MM/YYYY',                    ],
                    [
                        'key'             => 'nationality',
                        'label'           => 'Nationality',
                        'keywords'        => ['สัญชาติ', 'Nationality'],
                        'regex'           => '(?:Nationality|สัญชาติ)\\s*[:：]?\\s*([A-Z][A-Za-z]{2,20})',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'nationality_th',
                        'label'           => 'สัญชาติ (ไทย)',
                        'keywords'        => ['สัญชาติ'],
                        'regex'           => 'สัญชาติ\\s*[:：]?\\s*([ก-๙]+)',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'work_category',
                        'label'           => 'ประเภทงาน',
                        'keywords'        => ['ประเภทงานที่ได้รับอนุญาต', 'ประเภททงานที่ได้รับอนุญาต', 'Permitted Category of Work'],
                        'regex'           => '(?:ประเภท(?:ท)?งานที่ได้รับอนุญาต|Permitted\\s*Category\\s*of\\s*Work)\\s*[:：]?\\s*([ก-๙A-Za-z\\s]+)',
                        'extraction_mode' => 'same_line',
                    ],
                    [
                        'key'             => 'issue_date',
                        'label'           => 'Date of Issue',
                        'keywords'        => ['วันออกใบอนุญาตทำงาน', 'วันออกใบอนุญาตทํางาน', 'Date of Issue'],
                        'regex'           => '(?:Date\\s*of\\s*Issue)\\s*[:：]?\\s*(\\d{1,2}\\s+[A-Za-z]{3,9}\\s+\\d{4})',
                        'extraction_mode' => 'same_line',                        'format'          => 'date:DD/MM/YYYY',                    ],
                    [
                        'key'             => 'issue_date_th',
                        'label'           => 'วันออกใบอนุญาต (พ.ศ.)',
                        'keywords'        => ['วันออกใบอนุญาตทำงาน', 'วันออกใบอนุญาตทํางาน'],
                        'regex'           => '(?:วันออกใบอนุญาตท(?:ำ|ํา)งาน)(?:[^\\d]*?)(\\d{1,2}\\s+[ก-๙\\.]+\\s+\\d{4})',
                        'extraction_mode' => 'auto',                        'format'          => 'date:DD เดือนไทย YYYY+543',                    ],
                    [
                        'key'             => 'expiry_date',
                        'label'           => 'Date of Expiry',
                        'keywords'        => ['วันสิ้นสุดใบอนุญาตทำงาน', 'วันสิ้นสุดใบอนุญาตทํางาน', 'Date of Expiry'],
                        'regex'           => '(?:Date\\s*of\\s*Expiry)\\s*[:：]?\\s*(\\d{1,2}\\s+[A-Za-z]{3,9}\\s+\\d{4})',
                        'extraction_mode' => 'same_line',                        'format'          => 'date:DD/MM/YYYY',                    ],
                    [
                        'key'             => 'expiry_date_th',
                        'label'           => 'วันสิ้นสุดใบอนุญาต (พ.ศ.)',
                        'keywords'        => ['วันสิ้นสุดใบอนุญาตทำงาน', 'วันสิ้นสุดใบอนุญาตทํางาน'],
                        'regex'           => '(?:วันสิ้นสุดใบอนุญาตท(?:ำ|ํา)งาน)\\s*[:：]?\\s*(\\d{1,2}\\s+[ก-๙\\.]+\\s+\\d{4})',
                        'extraction_mode' => 'same_line',                        'format'          => 'date:DD เดือนไทย YYYY+543',                    ],
                    [
                        'key'             => 'barcode_number',
                        'label'           => 'Barcode Number',
                        'keywords'        => [],
                        'regex'           => '(\\d{4}\\s+\\d\\s+\\d{4}\\s+\\d\\s+\\d{3})',
                        'extraction_mode' => 'auto',
                        'transform'       => ['remove_spaces'],
                    ],
                ],
                'detection_landmarks' => [
                    ['type' => 'keyword', 'value' => 'ใบอนุญาตทำงาน',             'weight' => 80],
                    ['type' => 'keyword', 'value' => 'WORK PERMIT',                'weight' => 70],
                    ['type' => 'keyword', 'value' => 'Work Permit No',             'weight' => 40],
                    ['type' => 'keyword', 'value' => 'ผู้รับอนุญาตให้ทำงาน',        'weight' => 40],
                    ['type' => 'keyword', 'value' => 'Permitted Category of Work', 'weight' => 30],
                    ['type' => 'keyword', 'value' => 'วันออกใบอนุญาตทำงาน',        'weight' => 25],
                    ['type' => 'keyword', 'value' => 'วันสิ้นสุดใบอนุญาตทำงาน',     'weight' => 25],
                    ['type' => 'not_keyword', 'value' => 'PASSPORT',               'weight' => 60],
                    ['type' => 'not_keyword', 'value' => 'เลขประจำตัวประชาชน',      'weight' => 50],
                ],
                'is_active' => true,
            ]
        );
    }
}
