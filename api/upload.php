<?php

/**
 * ============================================================
 * File: api/upload.php
 * Deskripsi: REST API upload (X-API-Key, max 1 file) — Tahap 4
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
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/api.php';
require_once dirname(__DIR__) . '/includes/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('METHOD_NOT_ALLOWED', 'Gunakan POST.', 405);
}
$user = require_api_key();
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
try {
    if (!check_rate_limit('api:' . $user['api_key'], 'api_upload', 100)) {
        log_api((int) $user['id'], '/api/upload', 'POST', 429);
        api_error('RATE_LIMITED', 'Terlalu banyak request. Coba lagi nanti.', 429);
    }
} catch (PDOException $e) {
    error_log('API rate gagal: ' . $e->getMessage());
}
$file = $_FILES['image'] ?? $_FILES['file'] ?? null;
if ($file === null || !is_array($file)) {
    log_api((int) $user['id'], '/api/upload', 'POST', 400);
    api_error('VALIDATION_MISSING', 'Field file/image wajib diisi.', 400);
}
$r = handle_upload($file, (int) $user['id'], $ip, $_SERVER['HTTP_USER_AGENT'] ?? '', $_POST['expires_in'] ?? 'never');
if (!$r['ok']) {
    log_api((int) $user['id'], '/api/upload', 'POST', 400);
    api_error('UPLOAD_FAILED', $r['error'] ?? 'Upload gagal.', 400);
}
$stmt = getDB()->prepare('SELECT id, size, views FROM images WHERE filename = ?');
$stmt->execute([$r['filename']]);
$row = $stmt->fetch();
log_api((int) $user['id'], '/api/upload', 'POST', 201);
api_success(['id' => (int) ($row['id'] ?? 0), 'url' => $r['url'], 'size' => (int) ($row['size'] ?? 0), 'views' => 0], 201);
