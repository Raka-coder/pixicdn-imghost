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

// BMP 54-byte: getimagesize baca dimensi dari header tanpa load piksel.
// Klaim 10000x10000 (=100MP) dalam file 54 byte — simulasi decompression-bomb.
$tmp_bomb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pixicdn_test_bomb.bmp';
file_put_contents(
    $tmp_bomb,
    'BM' . pack('V', 54) . "\0\0\0\0" . pack('V', 54)
    . pack('V', 40) . pack('V', 10000) . pack('V', 10000)
    . pack('v', 1) . pack('v', 24) . str_repeat("\0", 32)
);
$bomb = ['name' => 'big.jpg', 'type' => 'image/jpeg', 'tmp_name' => $tmp_bomb, 'error' => UPLOAD_ERR_OK, 'size' => 54];
check('dimensi raksasa ditolak', validate_image_file($bomb) !== null);
@unlink($tmp_bomb);

// BMP mungil 1x1 lolos cek piksel (bukti guard tidak menolak file normal)
$tmp_tiny = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pixicdn_test_tiny.bmp';
file_put_contents(
    $tmp_tiny,
    'BM' . pack('V', 58) . "\0\0\0\0" . pack('V', 54)
    . pack('V', 40) . pack('V', 1) . pack('V', 1)
    . pack('v', 1) . pack('v', 24) . "\0\0\0" . "\0\0\0\0"
);
$tiny = ['name' => 'tiny.jpg', 'type' => 'image/jpeg', 'tmp_name' => $tmp_tiny, 'error' => UPLOAD_ERR_OK, 'size' => 58];
check('dimensi mungil lolos', validate_image_file($tiny) === null);
@unlink($tmp_tiny);

check('batas piksel terdefinisi', defined('MAX_IMAGE_PIXELS') && MAX_IMAGE_PIXELS === 25000000);

$key = build_s3_key('20261010_abc123.jpg');
check('s3 key prefix', str_starts_with($key, 'uploads/' . date('Y') . '/'));
check('s3 key suffix', str_ends_with($key, '20261010_abc123.jpg'));

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo "RESULT: semua lolos" . PHP_EOL;
