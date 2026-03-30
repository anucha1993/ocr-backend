<?php

namespace App\Services;

class OcrValidationService
{
    /**
     * Validate extracted OCR data and return errors per field.
     *
     * @param  array  $data  Extracted key-value pairs (field_key => value)
     * @return array  ['valid' => bool, 'errors' => [...], 'warnings' => [...]]
     */
    public static function validate(array $data): array
    {
        $errors   = [];
        $warnings = [];

        // ── Thai ID Card (13 digits + checksum) ───────────────
        if (!empty($data['id_card'])) {
            $id = preg_replace('/[^0-9]/', '', $data['id_card']);
            if (strlen($id) !== 13) {
                $errors['id_card'] = 'เลขบัตรประชาชนต้องมี 13 หลัก';
            } elseif (!self::validateThaiId($id)) {
                $warnings['id_card'] = 'เลขบัตรประชาชนไม่ผ่าน checksum';
            }
        }

        // ── Passport Number (6-9 alphanumeric) ────────────────
        if (!empty($data['passport_no'])) {
            $pp = strtoupper(trim($data['passport_no']));
            if (!preg_match('/^[A-Z0-9]{6,9}$/', $pp)) {
                $errors['passport_no'] = 'เลข Passport ต้องเป็นตัวอักษร/ตัวเลข 6-9 ตัว';
            }
        }

        // ── Names ─────────────────────────────────────────────
        foreach (['firstname', 'lastname'] as $field) {
            if (!empty($data[$field])) {
                $name = trim($data[$field]);
                if (strlen($name) < 2) {
                    $warnings[$field] = 'ชื่อสั้นเกินไป อาจ OCR อ่านผิด';
                }
                if (preg_match('/[0-9]{3,}/', $name)) {
                    $errors[$field] = 'ชื่อไม่ควรมีตัวเลขหลายตัว';
                }
            }
        }

        // ── Dates validation ──────────────────────────────────
        $today = date('Y-m-d');

        if (!empty($data['birthdate'])) {
            $bd = self::parseDate($data['birthdate']);
            if (!$bd) {
                $errors['birthdate'] = 'วันเกิดไม่ถูกต้อง';
            } elseif ($bd > $today) {
                $errors['birthdate'] = 'วันเกิดเป็นวันในอนาคต';
            } elseif ($bd < '1900-01-01') {
                $warnings['birthdate'] = 'วันเกิดอาจไม่ถูกต้อง (ก่อน ค.ศ. 1900)';
            }
        }

        if (!empty($data['issue_date'])) {
            $id = self::parseDate($data['issue_date']);
            if (!$id) {
                $errors['issue_date'] = 'วันออกเอกสารไม่ถูกต้อง';
            } elseif ($id > $today) {
                $warnings['issue_date'] = 'วันออกเอกสารเป็นวันในอนาคต';
            }
        }

        if (!empty($data['expiry_date'])) {
            $ed = self::parseDate($data['expiry_date']);
            if (!$ed) {
                $errors['expiry_date'] = 'วันหมดอายุไม่ถูกต้อง';
            } elseif ($ed < $today) {
                $warnings['expiry_date'] = 'เอกสารหมดอายุแล้ว';
            }
        }

        // Issue date must be before expiry date
        if (!empty($data['issue_date']) && !empty($data['expiry_date'])) {
            $id = self::parseDate($data['issue_date']);
            $ed = self::parseDate($data['expiry_date']);
            if ($id && $ed && $id >= $ed) {
                $errors['issue_date'] = 'วันออกเอกสารต้องก่อนวันหมดอายุ';
            }
        }

        // ── Nationality ───────────────────────────────────────
        if (!empty($data['nationality'])) {
            $nat = trim($data['nationality']);
            if (strlen($nat) < 2) {
                $warnings['nationality'] = 'สัญชาติสั้นเกินไป อาจ OCR อ่านผิด';
            }
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate Thai national ID checksum (Mod 11).
     */
    private static function validateThaiId(string $id): bool
    {
        if (strlen($id) !== 13) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $id[$i] * (13 - $i);
        }
        $check = (11 - ($sum % 11)) % 10;

        return $check === (int) $id[12];
    }

    /**
     * Try to parse a date string into Y-m-d format.
     */
    private static function parseDate(string $value): ?string
    {
        $value = trim($value);

        // Already Y-m-d
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // d/m/Y or d-m-Y
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $m)) {
            $year = (int) $m[3];
            // Buddhist era conversion
            if ($year > 2400) {
                $year -= 543;
            }
            return sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
