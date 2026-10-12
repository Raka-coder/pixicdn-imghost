<?php

/**
 * ============================================================
 * File: api/delete.php
 * Deskripsi: REST API hapus gambar milik sendiri — Tahap 4
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/config.example.php'; // dev-fill saja, guarded defined()
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/api.php';
require_once dirname(__DIR__) . '/includes/s3.php';
require_once dirname(__DIR__) . '/includes/upload.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    api_error('METHOD_NOT_ALLOWED', 'Gunakan DELETE.', 405);
}
$user = require_api_key();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    // Dukung /api/delete.php/123 via PATH_INFO
    $path = trim($_SERVER['PATH_INFO'] ?? '', '/');
    $id = (int) $path;
}
if ($id <= 0) {
    log_api((int) $user['id'], '/api/delete', $_SERVER['REQUEST_METHOD'], 400);
    api_error('VALIDATION_MISSING', 'ID gambar wajib diisi.', 400);
}
try {
    if (!check_rate_limit('api:' . $user['api_key'], 'api_delete', 100)) {
        log_api((int) $user['id'], '/api/delete', $_SERVER['REQUEST_METHOD'], 429);
        api_error('RATE_LIMITED', 'Terlalu banyak request.', 429);
    }
} catch (PDOException $e) {
    error_log('API rate gagal: ' . $e->getMessage());
}
$stmt = getDB()->prepare('SELECT * FROM images WHERE id = ? AND is_deleted = FALSE');
$stmt->execute([$id]);
$img = $stmt->fetch();
if (!$img || (int) $img['user_id'] !== (int) $user['id']) {
    log_api((int) $user['id'], '/api/delete', $_SERVER['REQUEST_METHOD'], 404);
    api_error('NOT_FOUND', 'Gambar tidak ditemukan.', 404);
}
s3_delete((string) $img['s3_key']);
getDB()->prepare('UPDATE images SET is_deleted = TRUE, deleted_at = NOW() WHERE id = ?')->execute([$id]);
getDB()->prepare('UPDATE users SET storage_used = GREATEST(0, storage_used - ?) WHERE id = ?')->execute([(int) $img['size'], (int) $user['id']]);
log_api((int) $user['id'], '/api/delete', $_SERVER['REQUEST_METHOD'], 200);
api_success(['deleted' => $id]);
