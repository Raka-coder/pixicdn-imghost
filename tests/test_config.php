<?php
/**
 * ============================================================
 * File: tests/test_config.php
 * Deskripsi: Pastikan config.example.php hanya placeholder + guards
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

$failures = 0;
function check(string $name, bool $cond): void
{
    global $failures;
    echo ($cond ? 'PASS' : 'FAIL') . ": $name" . PHP_EOL;
    if (!$cond) {
        $failures++;
    }
}

$src = file_get_contents(dirname(__DIR__) . '/config.example.php');
check('example memakai guards defined()', str_contains($src, "defined('DB_HOST')"));
check('example memakai placeholder CHANGE_ME', str_contains($src, 'CHANGE_ME'));
check('tanpa password contoh', !str_contains($src, 'ganti-password-ini'));
check('tanpa secret contoh', !str_contains($src, 'ganti-secret-ini'));
check('tanpa rustfsadmin', !str_contains($src, 'rustfsadmin'));
check('baca env via getenv', str_contains($src, 'getenv('));

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo 'RESULT: semua lolos' . PHP_EOL;
