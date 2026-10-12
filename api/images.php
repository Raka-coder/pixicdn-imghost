<?php

/**
 * ============================================================
 * File: api/images.php
 * Deskripsi: REST API list gambar user (pagination) — Tahap 4
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('METHOD_NOT_ALLOWED', 'Gunakan GET.', 405);
}
$user = require_api_key();
try {
    if (!check_rate_limit('api:' . $user['api_key'], 'api_list', 1000)) {
        log_api((int) $user['id'], '/api/images', 'GET', 429);
        api_error('RATE_LIMITED', 'Terlalu banyak request.', 429);
    }
} catch (PDOException $e) {
    error_log('API rate gagal: ' . $e->getMessage());
}
require_once dirname(__DIR__) . '/includes/upload.php';

$page = max(1, (int) ($_GET['page'] ?? 1));
$per = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
$off = ($page - 1) * $per;
$total = (int) getDB()->query('SELECT COUNT(*) FROM images WHERE user_id = ' . (int) $user['id'] . ' AND is_deleted = FALSE')->fetchColumn();
$stmt = getDB()->prepare("SELECT id, filename, public_url, thumbnail_url, size, views, uploaded_at FROM images WHERE user_id = ? AND is_deleted = FALSE ORDER BY uploaded_at DESC LIMIT $per OFFSET $off");
$stmt->execute([(int) $user['id']]);
$items = array_map(static function (array $r): array {
    return ['id' => (int) $r['id'], 'url' => $r['public_url'], 'thumbnail' => $r['thumbnail_url'], 'size' => (int) $r['size'], 'views' => (int) $r['views'], 'uploaded_at' => $r['uploaded_at']];
}, $stmt->fetchAll());
$total_pages = max(1, (int) ceil($total / $per));
log_api((int) $user['id'], '/api/images', 'GET', 200);
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'data' => $items,
    'pagination' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'total_pages' => $total_pages, 'has_next' => $page < $total_pages, 'has_prev' => $page > 1],
    'meta' => ['timestamp' => date(DATE_ATOM)],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
