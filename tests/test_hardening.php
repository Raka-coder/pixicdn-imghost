<?php

/**
 * ============================================================
 * File: tests/test_hardening.php
 * Deskripsi: Smoke test Tahap 6 (wiring hardening, bukan unit murni)
 * - login.php memakai check_rate_limit + pesan 429 generik
 * - album.php publik memakai get_album allow_public + cek is_public
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

$login = is_file(dirname(__DIR__) . '/auth/login.php') ? file_get_contents(dirname(__DIR__) . '/auth/login.php') : '';
check('login rate-limit wiring', str_contains($login, 'check_rate_limit') && str_contains($login, 'Terlalu banyak percobaan'));
check('login tidak bocor enumerasi', !str_contains(strtolower($login), 'username tidak terdaftar'));

$album = is_file(dirname(__DIR__) . '/album.php') ? file_get_contents(dirname(__DIR__) . '/album.php') : '';
check('album publik ada', $album !== '');
check('album pakai allow_public', str_contains($album, 'allow_public'));
check('album hormati is_public', str_contains($album, 'is_public'));
check('album wajib login TIDAK dipaksa', !str_contains($album, 'require_login()'));

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo 'RESULT: semua lolos' . PHP_EOL;
