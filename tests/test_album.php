<?php
/**
 * ============================================================
 * File: tests/test_album.php
 * Deskripsi: TDD RED test Tahap 5 (album + expiry murni)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
require_once dirname(__DIR__) . '/includes/album.php';

$failures = 0;
function check(string $name, bool $cond): void
{
    global $failures;
    echo ($cond ? 'PASS' : 'FAIL') . ": $name" . PHP_EOL;
    if (!$cond) {
        $failures++;
    }
}

check('slug dasar', slugify('Halo Dunia!') === 'halo-dunia');
check('slug simbol', slugify('Foto_Ku 2026 @Pantai') === 'foto-ku-2026-pantai');
check('slug kosong fallback', slugify('!!!') === 'album');
check('nama valid', validate_album_name('Liburan 2026') === true);
check('nama kosong', validate_album_name('  ') === false);
check('nama 101 char', validate_album_name(str_repeat('a', 101)) === false);
check('expiry never', expiry_to_sql('never') === null);
check('expiry 7d', expiry_to_sql('7d') === 'INTERVAL 7 DAY');
check('expiry invalid', expiry_to_sql('x') === null);

if ($failures > 0) {
    echo "RESULT: $failures gagal" . PHP_EOL;
    exit(1);
}
echo 'RESULT: semua lolos' . PHP_EOL;
