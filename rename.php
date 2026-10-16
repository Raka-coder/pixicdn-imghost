<?php

/**
 * ============================================================
 * File: rename.php
 * Deskripsi: Rename gambar milik sendiri (DB + S3 copy, best-effort cleanup).
 *   POST-only + CSRF + login + ownership. Redirect ke gallery dengan
 *   flash: ?ok=1 sukses, ?rename_error=<kode> gagal.
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/s3.php';
require_once __DIR__ . '/includes/upload.php';
require_once __DIR__ . '/includes/thumbnail.php';

start_secure_session();
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    redirect('/gallery.php?rename_error=invalid');
}
$user = current_user();
if ($user === null) {
    redirect('/auth/login.php');
}
$uid = (int) $user['id'];
$fail = static function (string $code) {
    redirect('/gallery.php?rename_error=' . $code);
};

try {
    if (!check_rate_limit('rename:' . $uid, 'rename', 30, 3600)) {
        $fail('limited');
    }
} catch (PDOException $e) {
    error_log('Rename rate gagal: ' . $e->getMessage());
}

$id = (int) ($_POST['image_id'] ?? 0);
if ($id <= 0) {
    $fail('notfound');
}
try {
    $stmt = getDB()->prepare('SELECT * FROM images WHERE id = ? AND is_deleted = FALSE');
    $stmt->execute([$id]);
    $img = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Rename baca gagal: ' . $e->getMessage());
    $fail('failed');
}
if (!$img || (int) $img['user_id'] !== $uid) {
    $fail('notfound');
}

$old_ext = strtolower((string) pathinfo((string) $img['filename'], PATHINFO_EXTENSION));
$new = trim((string) ($_POST['new_filename'] ?? ''));
$verr = validate_rename_filename($new, $old_ext);
if ($verr !== null) {
    $fail($verr === 'charset' || $verr === 'dotfile' ? 'invalid' : $verr);
}
try {
    $stmt = getDB()->prepare('SELECT id FROM images WHERE filename = ? AND is_deleted = FALSE AND id <> ?');
    $stmt->execute([$new, $id]);
    if ($stmt->fetch()) {
        $fail('taken');
    }
} catch (PDOException $e) {
    error_log('Rename unik gagal: ' . $e->getMessage());
    $fail('failed');
}

// Kunci S3 baru di direktori yang sama dengan kunci lama
$old_key = (string) $img['s3_key'];
$new_key = trim(dirname($old_key), '/') . '/' . $new;
if (!s3_copy($old_key, $new_key)) {
    $fail('failed');
}
$new_url = rtrim(S3_PUBLIC_URL, '/') . '/' . ltrim($new_key, '/');

// Thumbnail: salin bila key lama bisa diturunkan, kalau tidak generate ulang
$new_thumb_name = 'thumb_' . pathinfo($new, PATHINFO_FILENAME) . '.jpg';
$new_thumb_url = $img['thumbnail_url'];
$new_thumb_key = null;
$base = rtrim((string) S3_PUBLIC_URL, '/') . '/';
$old_thumb_key = null;
$thumb_copied = false;
if (is_string($img['thumbnail_url']) && str_starts_with($img['thumbnail_url'], $base)) {
    $old_thumb_key = substr($img['thumbnail_url'], strlen($base));
    if ($old_thumb_key !== '') {
        $new_thumb_key = trim(dirname($old_thumb_key), '/') . '/' . $new_thumb_name;
        if (s3_copy($old_thumb_key, $new_thumb_key)) {
            $new_thumb_url = $base . $new_thumb_key;
            $thumb_copied = true;
        }
    }
}
if (!$thumb_copied) {
    $tmp_dl = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rename_' . bin2hex(random_bytes(8));
    $tmp_th = $tmp_dl . '_thumb.jpg';
    $new_thumb_key = sprintf('thumbnails/%s/%s/%s', date('Y'), date('m'), $new_thumb_name);
    if (s3_download($new_key, $tmp_dl) && make_thumbnail($tmp_dl, $tmp_th) && s3_put($tmp_th, $new_thumb_key)) {
        $new_thumb_url = $base . $new_thumb_key;
    } else {
        error_log('Rename thumb gagal id=' . $id . ', pakai URL lama');
        $new_thumb_url = $img['thumbnail_url'];
        $new_thumb_key = null;
    }
    @unlink($tmp_dl);
    @unlink($tmp_th);
}

try {
    getDB()->prepare('UPDATE images SET filename = ?, public_url = ?, thumbnail_url = ? WHERE id = ? AND user_id = ?')->execute([
        $new, $new_url, $new_thumb_url, $id, $uid,
    ]);
} catch (PDOException $e) {
    error_log('Rename DB gagal: ' . $e->getMessage());
    // Rollback best-effort: hapus objek baru agar tidak yatim
    s3_delete($new_key);
    if (isset($new_thumb_key) && is_string($new_thumb_key)) {
        s3_delete($new_thumb_key);
    }
    $fail('failed');
}

// Bersihkan objek lama (best-effort, kegagalan hanya dicatat)
if (!s3_delete($old_key)) {
    error_log('Rename cleanup main gagal id=' . $id);
}
if (is_string($old_thumb_key) && $old_thumb_key !== '' && (!isset($new_thumb_key) || $new_thumb_key !== $old_thumb_key)) {
    if (!s3_delete($old_thumb_key)) {
        error_log('Rename cleanup thumb gagal id=' . $id);
    }
}
redirect('/gallery.php?ok=1');
