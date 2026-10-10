<?php
/**
 * ============================================================
 * File: tests/test_api.php
 * Deskripsi: TDD RED test Tahap 4 (API key + thumbnail murni)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
require_once dirname(__DIR__) . '/includes/api.php';
require_once dirname(__DIR__) . '/includes/thumbnail.php';

$failures = 0;
function check(string $name, bool $cond): void
{
    global $failures;
    echo ($cond ? 'PASS' : 'FAIL') . ": $name" . PHP_EOL;
    if (!$cond) {
        $failures++;
    }
}

$k = generate_api_key();
check('key 64 char', strlen($k) === 64);
check('key hex', (bool) preg_match('/^[a-f0-9]{64}$/', $k));
check('key unik', generate_api_key() !== $k);

check('landscape 1920x1080', thumb_dimensions(1920, 1080, 300) === [300, 169]);
check('portrait 1080x1920', thumb_dimensions(1080, 1920, 300) === [169, 300]);
check('kecil tidak upscale', thumb_dimensions(100, 80, 300) === [100, 80]);
check('persegi 500', thumb_dimensions(500, 500, 300) === [300, 300]);

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo 'RESULT: semua lolos' . PHP_EOL;
