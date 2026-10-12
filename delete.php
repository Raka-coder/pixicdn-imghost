<?php

/**
 * ============================================================
 * File: delete.php
 * Deskripsi: Hapus gambar (ownership + S3 + soft-delete + quota)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php'; // dev-fill saja, guarded defined()
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/s3.php';

start_secure_session();
require_login(); // Wajib akun — hanya pemilik yang bisa hapus
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Akses ditolak');
}
$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID tidak valid');
}
try {
    $stmt = getDB()->prepare('SELECT * FROM images WHERE id = ? AND is_deleted = FALSE');
    $stmt->execute([$id]);
    $img = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Delete baca gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada sistem.');
}
if (!$img) {
    http_response_code(404);
    exit('Gambar tidak ditemukan');
}

$user = current_user();
$allowed = $user !== null && (int) $img['user_id'] === (int) $user['id'];
if (!$allowed) {
    http_response_code(403);
    exit('Akses ditolak');
}

try {
    s3_delete((string) $img['s3_key']);
    getDB()->prepare('UPDATE images SET is_deleted = TRUE, deleted_at = NOW() WHERE id = ?')->execute([$id]);
    getDB()->prepare('UPDATE users SET storage_used = GREATEST(0, storage_used - ?) WHERE id = ?')->execute([(int) $img['size'], (int) $img['user_id']]);
} catch (PDOException $e) {
    error_log('Delete gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada sistem.');
}
redirect('/gallery.php?deleted=1');
