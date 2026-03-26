<?php

namespace App\Services;

class OcrParserService
{
    /**
     * Extract fields from raw OCR text using configurable field definitions.
     *
     * @param string $rawText  The full OCR-extracted text.
     * @param array  $fields   Array of field definitions from OcrFieldMapping.
     *   Each field: ['key' => string, 'label' => string, 'keywords' => string[], 'regex' => ?string, 'extraction_mode' => ?string]
     *   extraction_mode: 'auto' (default), 'same_line', 'next_line'
     * @return array<string, string|null>  Extracted key-value pairs.
     */
    public function extract(string $rawText, array $fields): array
    {
        $normalizedText = $this->normalizeText($rawText);
        $result = [];

        // First: auto-detect all key-value pairs using the smart detection
        $detectedPairs = $this->detectKeyValuePairs($rawText);
        $detectedMap = [];
        foreach ($detectedPairs as $pair) {
            $detectedMap[mb_strtolower($pair['key'])] = $pair['value'];
        }

        foreach ($fields as $field) {
            $key = $field['key'];
            $value = null;
            $mode = $field['extraction_mode'] ?? 'auto';

            // 1. Try regex first (most specific, highest accuracy)
            if ($value === null && !empty($field['regex'])) {
                $value = $this->extractByRegex($normalizedText, $field['regex'], $mode);
            }

            // 2. Try matching field keywords against detected pairs
            if ($value === null && !empty($field['keywords'])) {
                foreach ($field['keywords'] as $keyword) {
                    $kwLower = mb_strtolower(trim($keyword));
                    $kwNorm = rtrim($kwLower, '.。:：');
                    foreach ($detectedMap as $dKey => $dVal) {
                        $dKeyNorm = rtrim($dKey, '.。:：');
                        if ($dKeyNorm === $kwNorm || mb_strpos($dKeyNorm, $kwNorm) !== false || mb_strpos($kwNorm, $dKeyNorm) !== false) {
                            $value = $dVal;
                            break 2;
                        }
                    }
                }
            }

            // 3. Try matching field label against detected pairs
            if ($value === null && !empty($field['label'])) {
                $labelLower = rtrim(mb_strtolower($field['label']), '.。:：');
                foreach ($detectedMap as $dKey => $dVal) {
                    $dKeyNorm = rtrim($dKey, '.。:：');
                    if ($dKeyNorm === $labelLower || mb_strpos($dKeyNorm, $labelLower) !== false || mb_strpos($labelLower, $dKeyNorm) !== false) {
                        $value = $dVal;
                        break;
                    }
                }
            }

            // 4. Fallback: keyword line matching
            if ($value === null && !empty($field['keywords'])) {
                $value = $this->extractByKeywords($normalizedText, $field['keywords'], $mode);
            }

            if ($value !== null) {
                $value = $this->cleanValue($value);
                // Apply field-level transforms
                if (!empty($field['transform'])) {
                    $value = $this->applyTransforms($value, (array) $field['transform']);
                }
                // Apply field-level format
                if (!empty($field['format'])) {
                    $value = $this->applyFormat($value, $field['format']);
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Extract value using a regex pattern.
     * The pattern should have a capture group (group 1) for the desired value.
     *
     * @param string $mode 'auto'|'same_line'|'next_line'
     */
    private function extractByRegex(string $text, string $pattern, string $mode = 'auto'): ?string
    {
        try {
            // Use line-by-line matching to prevent regex from spanning across lines
            $lines = preg_split('/\r?\n/', $text);
            foreach ($lines as $i => $line) {
                if (preg_match('~' . $pattern . '~iu', $line, $matches)) {
                    $value = trim($matches[1] ?? $matches[0]);

                    // For next_line mode, ignore same-line capture
                    if ($mode === 'next_line') {
                        $value = '';
                    }

                    if ($value !== '') {
                        return $value;
                    }

                    // Regex matched the label but capture group empty → try next line
                    if ($mode !== 'same_line' && isset($lines[$i + 1])) {
                        $nextLine = trim($lines[$i + 1]);
                        if ($nextLine !== '' && mb_strlen($nextLine) <= 60) {
                            return $nextLine;
                        }
                    }
                }
            }

            // Multi-line window pass for 'auto' mode:
            // Join consecutive lines and retry — handles label on one line, value on another
            if ($mode === 'auto') {
                $lineCount = count($lines);
                for ($windowSize = 2; $windowSize <= 5; $windowSize++) {
                    for ($i = 0; $i <= $lineCount - $windowSize; $i++) {
                        $group = array_slice($lines, $i, $windowSize);
                        $combined = implode(' ', $group);
                        if (preg_match('~' . $pattern . '~iu', $combined, $matches)) {
                            $value = trim($matches[1] ?? $matches[0]);
                            if ($value !== '') {
                                return $value;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Invalid regex — skip silently
        }

        return null;
    }

    /**
     * Extract value by finding a keyword and grabbing the text after it.
     * Works line-by-line and across lines.
     *
     * @param string $mode 'auto'|'same_line'|'next_line'
     */
    private function extractByKeywords(string $text, array $keywords, string $mode = 'auto'): ?string
    {
        $lines = preg_split('/\r?\n/', $text);

        foreach ($keywords as $keyword) {
            $kwLower = mb_strtolower(trim($keyword));

            // Search in full text — same-line capture (skip for next_line mode)
            if ($mode !== 'next_line') {
                $escapedKw = preg_quote($keyword, '/');
                $pattern = '/' . $escapedKw . '\s*[:：\-]?\s*(.+)/iu';

                if (preg_match($pattern, $text, $matches)) {
                    $value = trim($matches[1]);
                    // Take only the first line of the matched value
                    $value = preg_split('/\r?\n/', $value)[0];
                    if ($value !== '') {
                        return $value;
                    }
                }
            }

            // Line-by-line: find keyword, value on same or next line
            foreach ($lines as $i => $line) {
                $lineLower = mb_strtolower($line);
                if (mb_strpos($lineLower, $kwLower) !== false) {
                    // Extract text after keyword on the same line (skip for next_line mode)
                    if ($mode !== 'next_line') {
                        $afterKeyword = mb_substr($line, mb_strpos($lineLower, $kwLower) + mb_strlen($kwLower));
                        $afterKeyword = ltrim($afterKeyword, " \t:：-");
                        $afterKeyword = trim($afterKeyword);

                        if ($afterKeyword !== '') {
                            return $afterKeyword;
                        }
                    }

                    // Look at next line(s) for value (skip for same_line mode)
                    if ($mode !== 'same_line') {
                        for ($j = $i + 1; $j < count($lines) && $j <= $i + 2; $j++) {
                            $nextLine = trim($lines[$j]);
                            if ($nextLine !== '') {
                                return $nextLine;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Normalize OCR text: fix common issues.
     */
    private function normalizeText(string $text): string
    {
        // Replace multiple spaces with single space (per line)
        $text = preg_replace('/[^\S\r\n]+/', ' ', $text);

        // Trim each line
        $lines = preg_split('/\r?\n/', $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);

        // Remove excessive blank lines (keep max 1)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Apply transform operations to an extracted value.
     *
     * Supported transforms:
     *   'remove_spaces'  — strip all whitespace
     *   'uppercase'      — convert to UPPERCASE
     *   'lowercase'      — convert to lowercase
     *   'trim'           — trim leading/trailing whitespace (already done by cleanValue)
     *   'digits_only'    — keep only digits
     *   'alphanumeric'   — keep only letters and digits
     */
    private function applyTransforms(string $value, array $transforms): string
    {
        foreach ($transforms as $t) {
            switch ($t) {
                case 'remove_spaces':
                    $value = preg_replace('/\s+/', '', $value);
                    break;
                case 'uppercase':
                    $value = mb_strtoupper($value);
                    break;
                case 'lowercase':
                    $value = mb_strtolower($value);
                    break;
                case 'trim':
                    $value = trim($value);
                    break;
                case 'digits_only':
                    $value = preg_replace('/[^0-9]/', '', $value);
                    break;
                case 'alphanumeric':
                    $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
                    break;
            }
        }
        return $value;
    }

    /**
     * Clean an extracted value.
     */
    private function cleanValue(string $value): string
    {
        // Remove trailing punctuation that might have been captured
        $value = rtrim($value, '.,;:');

        // Collapse whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        $value = trim($value);

        return $value;
    }

    /**
     * Convert date strings like "27 DEC 2024", "07 MAY 1985" to "DD/MM/YYYY".
     */
    private function normalizeDate(string $value): string
    {
        $months = [
            'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04',
            'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AUG' => '08',
            'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DEC' => '12',
            'JANUARY' => '01', 'FEBRUARY' => '02', 'MARCH' => '03', 'APRIL' => '04',
            'JUNE' => '06', 'JULY' => '07', 'AUGUST' => '08',
            'SEPTEMBER' => '09', 'OCTOBER' => '10', 'NOVEMBER' => '11', 'DECEMBER' => '12',
        ];

        // Pattern: DD MON YYYY or DD-MON-YYYY or DD/MON/YYYY
        if (preg_match('/^(\d{1,2})[\s\/\-\.]([A-Za-z]+)[\s\/\-\.](\d{4})$/', $value, $m)) {
            $monthUpper = strtoupper($m[2]);
            if (isset($months[$monthUpper])) {
                return str_pad($m[1], 2, '0', STR_PAD_LEFT) . '/' . $months[$monthUpper] . '/' . $m[3];
            }
        }

        return $value;
    }

    /**
     * Apply a format rule to an extracted value.
     *
     * Supported prefixes:
     *   'date:DD/MM/YYYY'             — numeric date
     *   'date:YYYY-MM-DD'             — ISO date
     *   'date:DD/MM/YYYY+543'         — Buddhist Era numeric
     *   'date:DD MON YYYY'            — English month name
     *   'date:DD MON YYYY+543'        — English month name + BE year
     *   'date:DD เดือนไทย YYYY+543'   — Thai month name + BE year
     */
    private function applyFormat(string $value, string $format): string
    {
        if (!str_starts_with($format, 'date:')) {
            return $value;
        }

        $pattern = substr($format, 5); // remove 'date:' prefix
        $parsed = $this->parseAnyDate($value);

        if ($parsed === null) {
            return $value; // can't parse, return as-is
        }

        [$day, $month, $yearCE] = $parsed;

        $addBE = false;
        if (str_ends_with($pattern, '+543')) {
            $addBE = true;
            $pattern = substr($pattern, 0, -4); // remove '+543'
        }

        $year = $addBE ? $yearCE + 543 : $yearCE;

        $englishMonths = [
            1 => 'JAN', 2 => 'FEB', 3 => 'MAR', 4 => 'APR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AUG',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DEC',
        ];

        $thaiMonths = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];

        $dd = str_pad($day, 2, '0', STR_PAD_LEFT);
        $mm = str_pad($month, 2, '0', STR_PAD_LEFT);

        return match (trim($pattern)) {
            'DD/MM/YYYY'       => "{$dd}/{$mm}/{$year}",
            'YYYY-MM-DD'       => "{$year}-{$mm}-{$dd}",
            'DD MON YYYY'      => "{$dd} " . ($englishMonths[$month] ?? $mm) . " {$year}",
            'DD เดือนไทย YYYY' => "{$dd} " . ($thaiMonths[$month] ?? $mm) . " {$year}",
            default            => $value,
        };
    }

    /**
     * Parse a date string in various formats into [day, month, yearCE].
     *
     * Handles: DD/MM/YYYY, DD-MM-YYYY, DD MON YYYY (English),
     * DD เดือน YYYY (Thai abbreviated), DD.MM.YYYY.
     * Years > 2400 are treated as Buddhist Era and converted to CE.
     *
     * @return array{0:int,1:int,2:int}|null  [day, month, yearCE] or null
     */
    private function parseAnyDate(string $value): ?array
    {
        $value = trim($value);
        // Normalize whitespace (including non‑breaking spaces) to single ASCII space
        $value = preg_replace('/\s+/u', ' ', $value);

        // English month map
        $enMonths = [
            'JAN' => 1, 'JANUARY' => 1,
            'FEB' => 2, 'FEBRUARY' => 2,
            'MAR' => 3, 'MARCH' => 3,
            'APR' => 4, 'APRIL' => 4,
            'MAY' => 5,
            'JUN' => 6, 'JUNE' => 6,
            'JUL' => 7, 'JULY' => 7,
            'AUG' => 8, 'AUGUST' => 8,
            'SEP' => 9, 'SEPTEMBER' => 9,
            'OCT' => 10, 'OCTOBER' => 10,
            'NOV' => 11, 'NOVEMBER' => 11,
            'DEC' => 12, 'DECEMBER' => 12,
        ];

        // Thai month map (abbreviated + full)
        $thaiMonths = [
            'ม.ค.' => 1, 'มกราคม' => 1,
            'ก.พ.' => 2, 'กุมภาพันธ์' => 2,
            'มี.ค.' => 3, 'มีนาคม' => 3,
            'เม.ย.' => 4, 'เมษายน' => 4,
            'พ.ค.' => 5, 'พฤษภาคม' => 5,
            'มิ.ย.' => 6, 'มิถุนายน' => 6,
            'ก.ค.' => 7, 'กรกฎาคม' => 7,
            'ส.ค.' => 8, 'สิงหาคม' => 8,
            'ก.ย.' => 9, 'กันยายน' => 9,
            'ต.ค.' => 10, 'ตุลาคม' => 10,
            'พ.ย.' => 11, 'พฤศจิกายน' => 11,
            'ธ.ค.' => 12, 'ธันวาคม' => 12,
        ];

        // 1) Numeric: DD/MM/YYYY  DD-MM-YYYY  DD.MM.YYYY
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $value, $m)) {
            $year = (int) $m[3];
            if ($year > 2400) $year -= 543;
            return [(int) $m[1], (int) $m[2], $year];
        }

        // 2) English month: DD MON YYYY
        if (preg_match('/^(\d{1,2})[\s\/\-\.]([A-Za-z]+)[\s\/\-\.](\d{4})$/u', $value, $m)) {
            $mon = $enMonths[strtoupper($m[2])] ?? null;
            if ($mon !== null) {
                $year = (int) $m[3];
                if ($year > 2400) $year -= 543;
                return [(int) $m[1], $mon, $year];
            }
        }

        // 3) Thai month — explicit match (handles optional/missing spaces around month)
        $thaiAbbr = [
            'ม\.ค\.' => 1, 'ก\.พ\.' => 2, 'มี\.ค\.' => 3, 'เม\.ย\.' => 4,
            'พ\.ค\.' => 5, 'มิ\.ย\.' => 6, 'ก\.ค\.' => 7, 'ส\.ค\.' => 8,
            'ก\.ย\.' => 9, 'ต\.ค\.' => 10, 'พ\.ย\.' => 11, 'ธ\.ค\.' => 12,
        ];
        $thaiFull = [
            'มกราคม' => 1, 'กุมภาพันธ์' => 2, 'มีนาคม' => 3, 'เมษายน' => 4,
            'พฤษภาคม' => 5, 'มิถุนายน' => 6, 'กรกฎาคม' => 7, 'สิงหาคม' => 8,
            'กันยายน' => 9, 'ตุลาคม' => 10, 'พฤศจิกายน' => 11, 'ธันวาคม' => 12,
        ];

        // Build alternation: abbreviated first (escaped dots), then full names
        $abbrPattern = implode('|', array_keys($thaiAbbr));
        $fullPattern = implode('|', array_map(fn($n) => preg_quote($n, '/'), array_keys($thaiFull)));
        $thaiMonthRegex = $abbrPattern . '|' . $fullPattern;

        if (preg_match('/(\d{1,2})\s*(' . $thaiMonthRegex . ')\s*(\d{4})/u', $value, $m)) {
            // Resolve month number: try abbreviated (restore dots), then full
            $matched = $m[2];
            $mon = $thaiFull[$matched] ?? null;
            if ($mon === null) {
                // It matched an abbreviated name — look up in thaiMonths
                $mon = $thaiMonths[$matched] ?? null;
            }
            if ($mon !== null) {
                $year = (int) $m[3];
                if ($year > 2400) $year -= 543;
                return [(int) $m[1], $mon, $year];
            }
        }

        // 4) Fallback: generic DD <text> YYYY
        if (preg_match('/^(\d{1,2})\s+(.+?)\s+(\d{4})$/u', $value, $m)) {
            $mon = $thaiMonths[trim($m[2])] ?? null;
            if ($mon !== null) {
                $year = (int) $m[3];
                if ($year > 2400) $year -= 543;
                return [(int) $m[1], $mon, $year];
            }
        }

        return null;
    }

    /**
     * Auto-detect which template matches the OCR text by scoring detection landmarks.
     * Returns the best matching OcrFieldMapping, or null if no confident match.
     *
     * @param string $ocrText  The OCR text to analyze.
     * @param int    $minScore Minimum score to consider a match (default 50).
     * @return \App\Models\OcrFieldMapping|null
     */
    public function detectDocumentType(string $ocrText, int $minScore = 50): ?\App\Models\OcrFieldMapping
    {
        $templates = \App\Models\OcrFieldMapping::where('is_active', true)
            ->whereNotNull('detection_landmarks')
            ->get();

        $bestTemplate = null;
        $bestScore = 0;

        foreach ($templates as $template) {
            $landmarks = $template->detection_landmarks;
            if (empty($landmarks) || !is_array($landmarks)) {
                continue;
            }

            $score = $this->scoreLandmarks($ocrText, $landmarks);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTemplate = $template;
            }
        }

        return $bestScore >= $minScore ? $bestTemplate : null;
    }

    /**
     * Score OCR text against a set of detection landmarks.
     *
     * @param string $ocrText   The OCR text.
     * @param array  $landmarks Array of landmark rules.
     * @return int Total score.
     */
    public function scoreLandmarks(string $ocrText, array $landmarks): int
    {
        $score = 0;

        foreach ($landmarks as $landmark) {
            $type = $landmark['type'] ?? '';
            $value = $landmark['value'] ?? '';
            $weight = (int) ($landmark['weight'] ?? 0);

            switch ($type) {
                case 'mrz':
                    // Check if MRZ lines exist (long alphanumeric lines with '<' characters)
                    if (preg_match('/[A-Z0-9<]{30,}/', $ocrText) && substr_count($ocrText, '<') >= 5) {
                        $score += $weight;
                    }
                    break;

                case 'keyword':
                    if ($value !== '' && mb_stripos($ocrText, $value) !== false) {
                        $score += $weight;
                    }
                    break;

                case 'not_keyword':
                    if ($value !== '' && mb_stripos($ocrText, $value) !== false) {
                        $score -= abs($weight);
                    }
                    break;

                case 'regex':
                    if ($value !== '') {
                        try {
                            if (preg_match('~' . $value . '~iu', $ocrText)) {
                                $score += $weight;
                            }
                        } catch (\Throwable) {
                            // Invalid regex — skip
                        }
                    }
                    break;
            }
        }

        return $score;
    }

    /**
     * Auto-detect key-value pairs from OCR text.
     * Uses known document labels, colon-separated patterns, and MRZ parsing.
     *
     * @param string $rawText
     * @return array<int, array{key: string, value: string}>
     */
    public function detectKeyValuePairs(string $rawText): array
    {
        $normalizedText = $this->normalizeText($rawText);
        $pairs = [];
        $seen = [];

        // Helper: normalize key for dedup — lowercase, strip trailing dots/punctuation
        $normalizeKey = fn(string $key) => rtrim(mb_strtolower(trim($key)), '.。:：');

        // 1. Colon pairs first (most reliable — key:value on same line)
        $colonPairs = $this->extractColonPairs($normalizedText);
        foreach ($colonPairs as $p) {
            $keyNorm = $normalizeKey($p['key']);
            if (!isset($seen[$keyNorm])) {
                $seen[$keyNorm] = true;
                $pairs[] = $p;
            }
        }

        // 2. Known document labels (label on one line, value on next)
        $knownLabelPairs = $this->extractByKnownLabels($normalizedText);
        foreach ($knownLabelPairs as $p) {
            $keyNorm = $normalizeKey($p['key']);
            if (!isset($seen[$keyNorm])) {
                $seen[$keyNorm] = true;
                $pairs[] = $p;
            }
        }

        // 3. Parse MRZ lines — add only fields NOT already found above
        $mrzPairs = $this->parseMrz($normalizedText);
        foreach ($mrzPairs as $p) {
            // Strip " (MRZ)" suffix to compare against visual labels
            $baseKey = preg_replace('/\s*\(MRZ\)$/', '', $p['key']);
            $keyNorm = $normalizeKey($baseKey);

            if (!isset($seen[$keyNorm])) {
                $seen[$keyNorm] = true;
                $pairs[] = $p;
            }
        }

        return $pairs;
    }

    /**
     * Parse Machine Readable Zone (MRZ) lines from passport.
     * Handles TD3 (passport) format: 2 lines of 44 characters with '<' filler.
     */
    private function parseMrz(string $text): array
    {
        $pairs = [];
        $lines = preg_split('/\r?\n/', $text);

        // Find MRZ lines — lines with lots of '<' characters
        $mrzLines = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[A-Z0-9<]{30,}$/', $line) && substr_count($line, '<') >= 5) {
                $mrzLines[] = $line;
            }
        }

        if (count($mrzLines) < 2) {
            return $pairs;
        }

        // Use the last 2 MRZ lines (TD3 passport format)
        $line1 = $mrzLines[count($mrzLines) - 2];
        $line2 = $mrzLines[count($mrzLines) - 1];

        // Line 1: P<CTRYNAME<<GIVEN<NAMES<<<<<<<<<<<<<<<<<<
        // or: PJMMRSAN<YU<KHAING<<<<<<<<<<<<<<<<<<<<<
        if (preg_match('/^P([A-Z<])([A-Z]{3})([A-Z<]+)$/', $line1, $m)) {
            $type = rtrim($m[1], '<') ?: '';
            $country = $m[2];

            // Parse name: split by '<<', first part is surname, rest is given names
            $namePart = rtrim($m[3], '<');
            $nameParts = explode('<<', $namePart, 2);
            $surname = str_replace('<', ' ', trim($nameParts[0]));
            $givenNames = isset($nameParts[1]) ? str_replace('<', ' ', trim($nameParts[1])) : '';
            $fullName = trim($surname . ' ' . $givenNames);

            if ($type) {
                $pairs[] = ['key' => 'Type (MRZ)', 'value' => 'P' . $type];
            }
            if ($country && $country !== '<<<') {
                $pairs[] = ['key' => 'Country code (MRZ)', 'value' => $country];
            }
            if ($fullName) {
                $pairs[] = ['key' => 'Name (MRZ)', 'value' => $fullName];
            }
        }

        // Line 2: ME887622<7MMR8505073F2412274<<<<<<<<<<<<2
        // Format: DocNo(9) Check(1) Nationality(3) DOB-YYMMDD(6) Check(1) Sex(1) Expiry-YYMMDD(6) Check(1) ...
        if (strlen($line2) >= 28) {
            $docNo = rtrim(substr($line2, 0, 9), '<');
            $nationality = substr($line2, 10, 3);
            $dobRaw = substr($line2, 13, 6);
            $sex = substr($line2, 20, 1);
            $expiryRaw = substr($line2, 21, 6);

            if ($docNo && preg_match('/^[A-Z0-9]+$/', $docNo)) {
                $pairs[] = ['key' => 'Passport No (MRZ)', 'value' => $docNo];
            }
            if ($nationality && $nationality !== '<<<') {
                $pairs[] = ['key' => 'Nationality (MRZ)', 'value' => $nationality];
            }
            if (preg_match('/^\d{6}$/', $dobRaw)) {
                $pairs[] = ['key' => 'Date of birth (MRZ)', 'value' => $this->formatMrzDate($dobRaw)];
            }
            if (in_array($sex, ['M', 'F', '<'])) {
                $pairs[] = ['key' => 'Sex (MRZ)', 'value' => $sex === '<' ? 'Unspecified' : ($sex === 'M' ? 'Male' : 'Female')];
            }
            if (preg_match('/^\d{6}$/', $expiryRaw)) {
                $pairs[] = ['key' => 'Date of expiry (MRZ)', 'value' => $this->formatMrzDate($expiryRaw)];
            }
        }

        return $pairs;
    }

    /**
     * Format MRZ date YYMMDD to readable format.
     */
    private function formatMrzDate(string $yymmdd): string
    {
        $yy = substr($yymmdd, 0, 2);
        $mm = substr($yymmdd, 2, 2);
        $dd = substr($yymmdd, 4, 2);

        // Guess century: 00-40 → 2000s, 41-99 → 1900s
        $year = (int) $yy <= 40 ? '20' . $yy : '19' . $yy;

        return $dd . '/' . $mm . '/' . $year;
    }

    /**
     * Extract key-value pairs by scanning for known document labels in OCR text.
     * Handles both "Label Value" on same line and "Label\nValue" across lines.
     */
    private function extractByKnownLabels(string $text): array
    {
        // Comprehensive list of known passport/ID card field labels
        $knownLabels = [
            'Type', 'Passport Type',
            'Country code', 'Country Code', 'Code',
            'Passport No', 'Passport No.', 'Passport Number', 'Document No', 'Document Number',
            'Name', 'Full Name', 'Surname', 'Given Names', 'Given Name', 'First Name', 'Last Name', 'Family Name',
            'Nationality', 'Citizenship',
            'Date of birth', 'Date of Birth', 'Birth Date', 'DOB',
            'Sex', 'Gender',
            'Place of birth', 'Place of Birth', 'Birth Place',
            'Date of issue', 'Date of Issue', 'Issue Date', 'Issued',
            'Date of expiry', 'Date of Expiry', 'Expiry Date', 'Expiration Date', 'Valid Until',
            'Authority', 'Issuing Authority', 'Place of issue', 'Place of Issue',
            // Thai ID
            'เลขประจำตัวประชาชน', 'ชื่อ', 'นามสกุล', 'วันเกิด', 'ที่อยู่', 'วันออกบัตร', 'วันบัตรหมดอายุ',
            'Name', 'Last name', 'Date of Birth', 'Date of Issue', 'Date of Expiry',
            // Thai Work Permit
            'ใบอนุญาตทำงานเลขที่', 'ชื่อผู้รับอนุญาตให้ทำงาน', 'วัน เดือน ปีเกิด', 'สัญชาติ',
            'ประเภทงานที่ได้รับอนุญาต', 'วันออกใบอนุญาตทำงาน', 'วันสิ้นสุดใบอนุญาตทำงาน',
            'Work Permit No', 'Permitted Category of Work',
            // Other common
            'ID Number', 'Card Number', 'Registration No', 'Address', 'Occupation',
        ];

        // Sort by length descending to match longer labels first
        usort($knownLabels, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

        $lines = preg_split('/\r?\n/', $text);
        $pairs = [];
        $usedLineIndices = [];

        // First: identify which lines are labels and pre-assign their value lines
        $labelLineIndices = [];
        $valueLineIndices = []; // lines that are values of a label
        foreach ($lines as $i => $line) {
            $lineTrimmed = trim($line);
            if ($lineTrimmed === '') continue;
            if ($this->isKnownLabel($lineTrimmed, $knownLabels)) {
                $labelLineIndices[$i] = true;
            }
        }
        // Pre-scan: for each label, mark the next non-label, non-empty line as its value
        foreach ($labelLineIndices as $i => $true) {
            for ($j = $i + 1; $j < count($lines) && $j <= $i + 2; $j++) {
                $nextLine = trim($lines[$j]);
                if ($nextLine === '' || $this->isMrzLine($nextLine)) continue;
                if (!isset($labelLineIndices[$j])) {
                    $valueLineIndices[$j] = $i; // line $j is value of label at line $i
                    break;
                }
            }
        }

        foreach ($knownLabels as $label) {
            $escapedLabel = preg_quote($label, '~');

            foreach ($lines as $i => $line) {
                if (isset($usedLineIndices[$i])) continue;

                $lineTrimmed = trim($line);
                if ($lineTrimmed === '') continue;

                // Case-insensitive match for the label on this line
                if (!preg_match('~(?:^|\s)' . $escapedLabel . '(?:\s|[:：]|$)~iu', $lineTrimmed)) {
                    continue;
                }

                // Try to extract value after the label on the same line
                $pattern = '~' . $escapedLabel . '\s*[:：]?\s*(.+)$~iu';
                if (preg_match($pattern, $lineTrimmed, $m)) {
                    $value = trim($m[1]);
                    // Make sure value isn't just another label or MRZ junk
                    if ($value !== '' && !$this->isKnownLabel($value, $knownLabels) && !$this->isMrzLine($value)) {
                        $pairs[] = ['key' => $label, 'value' => $this->cleanValue($value)];
                        $usedLineIndices[$i] = true;
                        break; // Found this label, move to next
                    }
                }

                // Value on next line: look ahead, skip labels and their assigned values
                for ($j = $i + 1; $j < count($lines) && $j <= $i + 5; $j++) {
                    if (isset($usedLineIndices[$j])) continue;
                    $nextLine = trim($lines[$j]);
                    if ($nextLine === '') continue;
                    if ($this->isMrzLine($nextLine)) continue;

                    // Skip known label lines
                    if (isset($labelLineIndices[$j])) continue;

                    // Skip lines pre-assigned as values of OTHER labels
                    if (isset($valueLineIndices[$j]) && $valueLineIndices[$j] !== $i) continue;

                    $pairs[] = ['key' => $label, 'value' => $this->cleanValue($nextLine)];
                    $usedLineIndices[$i] = true;
                    $usedLineIndices[$j] = true;
                    break 2; // Found this label, move to next
                }
            }
        }

        return $pairs;
    }

    /**
     * Check if a line looks like an MRZ line (machine-readable zone).
     */
    private function isMrzLine(string $text): bool
    {
        $text = trim($text);
        return preg_match('/^[A-Z0-9<]{20,}$/', $text) && substr_count($text, '<') >= 3;
    }

    /**
     * Check if a string closely matches a known label.
     */
    private function isKnownLabel(string $text, array $knownLabels): bool
    {
        $textLower = mb_strtolower(trim($text));
        foreach ($knownLabels as $label) {
            if (mb_strtolower($label) === $textLower) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract explicit "Key : Value" pairs from lines with colon separators.
     * Only keeps pairs where the key looks like a real label (not a data value).
     */
    private function extractColonPairs(string $text): array
    {
        $lines = preg_split('/\r?\n/', $text);
        $pairs = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Match "Key : Value" or "Key: Value"
            if (!preg_match('/^(.{2,50}?)\s*[:：]\s*(.+)$/u', $line, $m)) {
                continue;
            }

            $key = trim($m[1]);
            $value = trim($m[2]);

            if ($key === '' || $value === '') continue;

            // Filter out bad keys:
            // - Pure numbers
            // - Very long keys (probably not a label)
            // - Keys that look like data values (dates, IDs, etc.)
            if (preg_match('/^\d+$/', $key)) continue;
            if (preg_match('/^\d{1,2}\s+\w{3}\s+\d{4}$/', $key)) continue; // Date like "07 MAY 1985"
            if (preg_match('/^[A-Z0-9]{6,}$/', $key)) continue; // Looks like a passport/ID number
            if (mb_strlen($key) > 40) continue;

            // Key should contain at least one letter
            if (!preg_match('/[a-zA-Z\p{Thai}\p{Myanmar}\p{Lao}\p{Khmer}]/u', $key)) continue;

            $pairs[] = ['key' => $key, 'value' => $this->cleanValue($value)];
        }

        return $pairs;
    }

    /**
     * Given per-page OCR texts, find the best page for extraction.
     * For passports: finds the page with MRZ or passport-related keywords.
     * Returns the text of the best page, or all text combined if no clear match.
     *
     * @param array $pageTexts Array of text strings, one per page.
     * @return string The text to use for extraction.
     */
    public function findBestPage(array $pageTexts): string
    {
        if (count($pageTexts) <= 1) {
            return $pageTexts[0] ?? '';
        }

        // Score each page — higher = more likely the passport bio page
        $scores = [];
        foreach ($pageTexts as $i => $text) {
            $score = 0;
            $textUpper = mb_strtoupper($text);

            // MRZ lines are the strongest signal
            if (preg_match('/[A-Z0-9<]{30,}/', $text) && substr_count($text, '<') >= 5) {
                $score += 100;
            }

            // Passport-specific keywords
            $passportKeywords = [
                'PASSPORT' => 20,
                'REPUBLIC' => 5,
                'Date of birth' => 15,
                'Date of expiry' => 15,
                'Date of issue' => 10,
                'Nationality' => 15,
                'Passport No' => 20,
                'Place of birth' => 10,
                'Country code' => 10,
                'Authority' => 5,
            ];

            foreach ($passportKeywords as $kw => $pts) {
                if (mb_stripos($text, $kw) !== false) {
                    $score += $pts;
                }
            }

            // Penalize pages with visa/immigration noise
            $noiseKeywords = ['VISAS', 'IMMIGRATION BUREAU', 'DEPARTURE CARD', 'T.M.6',
                'ADMITTED', 'NOTICE', 'ใบเสร็จรับเงิน', 'คำเตือน', 'Re-entry Permit',
                'แจ้ง 90 วัน', 'IMPORTANT NOTICE', 'Flight no'];
            foreach ($noiseKeywords as $noise) {
                if (mb_stripos($text, $noise) !== false) {
                    $score -= 30;
                }
            }

            $scores[$i] = $score;
        }

        // Pick the page with the highest score
        arsort($scores);
        $bestIdx = array_key_first($scores);

        // Only use the best page if it has a meaningful score
        if ($scores[$bestIdx] >= 30) {
            return $pageTexts[$bestIdx];
        }

        // Fallback: return all text combined
        return implode("\n\n", $pageTexts);
    }

    /**
     * Get default passport field definitions.
     * Used when no field mapping is specified.
     */
    public static function defaultPassportFields(): array
    {
        return [
            [
                'key'             => 'full_name',
                'label'           => 'Full Name',
                'keywords'        => ['Name', 'Full Name', 'Surname', 'Given Name', 'Given Names'],
                'regex'           => '(?:(?:Sur)?name|Given\s*Names?)\s*[:：/]?\s*([A-Z][A-Za-z .\-]{2,40})',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'passport_number',
                'label'           => 'Passport Number',
                'keywords'        => ['Passport No', 'Passport No.', 'Passport Number', 'Document No'],
                'regex'           => '(?:Passport\s*(?:No\.?|Number)|Document\s*No\.?)\s*[:：]?\s*([A-Z0-9]{6,12})',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'date_of_birth',
                'label'           => 'Date of Birth',
                'keywords'        => ['Date of Birth', 'Birth Date', 'DOB', 'D.O.B'],
                'regex'           => '(?:Date\s*of\s*Birth|Birth\s*Date|D\.?O\.?B\.?)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'expiry_date',
                'label'           => 'Expiry Date',
                'keywords'        => ['Date of Expiry', 'Expiry Date', 'Expiration Date', 'Valid Until', 'Expires'],
                'regex'           => '(?:Date\s*of\s*Expiry|Expiry\s*Date|Expiration\s*Date|Valid\s*Until|Expires)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'nationality',
                'label'           => 'Nationality',
                'keywords'        => ['Nationality', 'Citizen'],
                'regex'           => '(?:Nationality|Citizen(?:ship)?)\s*[:：]?\s*([A-Z][A-Za-z]{2,20})',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'gender',
                'label'           => 'Gender',
                'keywords'        => ['Sex', 'Gender'],
                'regex'           => '(?:Sex|Gender)\s*[:：/]?\s*(Male|Female|M|F)',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'place_of_birth',
                'label'           => 'Place of Birth',
                'keywords'        => ['Place of Birth', 'Birth Place'],
                'regex'           => '(?:Place\s*of\s*Birth|Birth\s*Place)\s*[:：]?\s*([A-Z][A-Za-z ,\-]{2,40})',
                'extraction_mode' => 'auto',
            ],
            [
                'key'             => 'issue_date',
                'label'           => 'Issue Date',
                'keywords'        => ['Date of Issue', 'Issue Date', 'Issued'],
                'regex'           => '(?:Date\s*of\s*Issue|Issue\s*Date|Issued)\s*[:：]?\s*(\d{1,2}[\s\/\-\.]\w{2,9}[\s\/\-\.]\d{2,4})',
                'extraction_mode' => 'auto',
            ],
        ];
    }
}
