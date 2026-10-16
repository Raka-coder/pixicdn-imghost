<?php

/**
 * ============================================================
 * File: dashboard/stats.php
 * Deskripsi: Endpoint JSON metrik dashboard untuk refresh realtime
 *   (fetch 60 detik dari dashboard/index.php). Auth via session —
 *   BUKAN API key. Read-only, rate-limited per user.
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/config.example.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/dashboard.php';

start_secure_session();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Gunakan GET.']);
    exit;
}
$user = current_user();
if ($user === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Wajib masuk dulu.']);
    exit;
}
$uid = (int) $user['id'];
try {
    if (!check_rate_limit('stats:' . $uid, 'stats', 120, 60)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Terlalu banyak request.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Stats rate gagal: ' . $e->getMessage());
}
try {
    $stats = get_dashboard_stats($uid);
} catch (PDOException $e) {
    error_log('Stats gagal: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Gagal memuat metrik.']);
    exit;
}
echo json_encode(['success' => true, 'data' => $stats, 'meta' => ['timestamp' => date(DATE_ATOM)]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
