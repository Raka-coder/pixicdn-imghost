<?php

/**
 * ============================================================
 * File: tests/test_upload.php
 * Deskripsi: TDD RED test Tahap 3 (validasi upload murni)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
require_once dirname(__DIR__) . '/includes/upload.php';

$failures = 0;
function check(string $name, bool $cond): void
{
    global $failures;
    echo ($cond ? 'PASS' : 'FAIL') . ": $name" . PHP_EOL;
    if (!$cond) {
        $failures++;
    }
}

$make = static function (string $name, int $size, int $err = UPLOAD_ERR_OK): array {
    return ['name' => $name, 'type' => 'image/jpeg', 'tmp_name' => '/tmp/x', 'error' => $err, 'size' => $size];
};

check('jpg valid', validate_image_file($make('foto.jpg', 1000)) === null);
check('webp valid', validate_image_file($make('a.webp', 1000)) === null);
check('php ditolak', validate_image_file($make('shell.php', 1000)) !== null);
check('exe ditolak', validate_image_file($make('x.exe', 1000)) !== null);
check('over 5MB ditolak', validate_image_file($make('big.jpg', 6 * 1024 * 1024)) !== null);
check('error upload ditolak', validate_image_file($make('a.jpg', 100, UPLOAD_ERR_NO_FILE)) !== null);

$key = build_s3_key('20261010_abc123.jpg');
check('s3 key prefix', str_starts_with($key, 'uploads/' . date('Y') . '/'));
check('s3 key suffix', str_ends_with($key, '20261010_abc123.jpg'));

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo "RESULT: semua lolos" . PHP_EOL;
