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

$auth = is_file(dirname(__DIR__) . '/includes/auth.php') ? file_get_contents(dirname(__DIR__) . '/includes/auth.php') : '';
check('auth punya dummy hash timing', str_contains($auth, 'DUMMY_PASSWORD_HASH'));
check('verify_login samakan waktu user-asing', str_contains($auth, 'password_verify($password, DUMMY_PASSWORD_HASH)'));
check('reset samakan waktu user-asing', str_contains($auth, 'password_verify($recovery_code, DUMMY_PASSWORD_HASH)'));
check('login regenerate session', str_contains($auth, 'session_regenerate_id(true)'));

$stats_ep = is_file(dirname(__DIR__) . '/dashboard/stats.php') ? file_get_contents(dirname(__DIR__) . '/dashboard/stats.php') : '';
check('endpoint stats ada', $stats_ep !== '');
check('endpoint stats guard session (401, tanpa redirect login)', str_contains($stats_ep, 'current_user()') && str_contains($stats_ep, '401') && !str_contains($stats_ep, 'require_login()'));
check('endpoint stats rate-limited', str_contains($stats_ep, 'check_rate_limit'));
check('endpoint stats hanya GET', str_contains($stats_ep, "REQUEST_METHOD'] !== 'GET'"));

$dash = is_file(dirname(__DIR__) . '/includes/dashboard.php') ? file_get_contents(dirname(__DIR__) . '/includes/dashboard.php') : '';
check('helper metrik dashboard ada', str_contains($dash, 'function get_dashboard_stats'));

$up = is_file(dirname(__DIR__) . '/upload.php') ? file_get_contents(dirname(__DIR__) . '/upload.php') : '';
check('upload xhr.open ada sebelum send', str_contains($up, "xhr.open('POST', '/upload_handler.php'") && strpos($up, 'xhr.open') < strpos($up, 'xhr.send'));
check('upload reset input file', str_contains($up, "input.value = ''"));
check('upload cegah klik preview', str_contains($up, 'stopPropagation'));
check('upload tolak file invalid dari antrean', str_contains($up, 'valid.push(f)'));

$handler = is_file(dirname(__DIR__) . '/upload_handler.php') ? file_get_contents(dirname(__DIR__) . '/upload_handler.php') : '';
check('handler kirim bbcode+html sesuai JS', str_contains($handler, "'bbcode'") && str_contains($handler, "'html'") && str_contains($up, 'd.bbcode') && str_contains($up, 'd.html'));

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
