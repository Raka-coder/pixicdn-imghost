<?php

/**
 * ============================================================
 * File: tests/test_auth.php
 * Deskripsi: TDD RED test untuk Tahap 2 Auth (pure functions)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$failures = 0;

function check(string $name, bool $cond): void
{
    global $failures;
    if ($cond) {
        echo "PASS: $name" . PHP_EOL;
    } else {
        $failures++;
        echo "FAIL: $name" . PHP_EOL;
    }
}

// Username: 3-30, alnum + underscore
check('username valid', validate_username('raka_restu123') === true);
check('username terlalu pendek', validate_username('ab') === false);
check('username karakter ilegal', validate_username('r@ka!') === false);

// Password: min 8
check('password valid', validate_password('password123') === true);
check('password pendek', validate_password('abc123') === false);

// Recovery code: 8 char, A-Z2-9 tanpa O0I1
$code = generate_recovery_code();
check('recovery code 8 char', strlen($code) === 8);
check('recovery code charset', (bool) preg_match('/^[A-Z2-9]{8}$/', $code));
check('recovery code tanpa O0I1', strpbrk($code, 'O0I1') === false);

// Bcrypt roundtrip cost 12
$hash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
check('bcrypt verify', password_verify('password123', $hash) === true);

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo 'RESULT: semua lolos' . PHP_EOL;
