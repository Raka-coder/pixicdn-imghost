<?php
/**
 * ============================================================
 * File: api/me.php
 * Deskripsi: REST API info user + quota — Tahap 4
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
$stmt = getDB()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(size),0) AS bytes, COALESCE(SUM(views),0) AS views FROM images WHERE user_id = ? AND is_deleted = FALSE');
$stmt->execute([(int) $user['id']]);
$agg = $stmt->fetch();
log_api((int) $user['id'], '/api/me', 'GET', 200);
api_success([
    'username' => $user['username'],
    'storage_used' => (int) $user['storage_used'],
    'storage_quota' => (int) $user['storage_quota'],
    'total_images' => (int) ($agg['total'] ?? 0),
    'total_views' => (int) ($agg['views'] ?? 0),
]);
