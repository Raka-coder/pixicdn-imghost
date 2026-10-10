<?php
/**
 * ============================================================
 * File: upload_handler.php
 * Deskripsi: Endpoint POST upload (max 5 file, rate limit 100/jam/IP)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.example.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/upload.php';

start_secure_session();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metode tidak diizinkan']);
    exit;
}
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token keamanan tidak valid']);
    exit;
}
$user = current_user();
if ($user === null) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Wajib masuk dulu. Daftar gratis untuk upload.']);
    exit;
}
$user_id = (int) $user['id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
try {
    if (!check_rate_limit($ip, 'upload', 100)) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Terlalu banyak upload. Coba lagi 1 jam lagi.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Rate limit gagal: ' . $e->getMessage());
}

$files = $_FILES['images'] ?? null;
if ($files === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tidak ada file.']);
    exit;
}
$normalized = [];
if (is_array($files['name'])) {
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $normalized[] = ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
    }
} else {
    $normalized[] = $files;
}
if (count($normalized) > MAX_FILES_PER_UPLOAD) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Maksimal 5 file sekaligus.']);
    exit;
}

$user_id = (int) $user['id'];
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$expires_opt = $_POST['expires_in'] ?? 'never';
$results = [];
foreach ($normalized as $file) {
    $r = handle_upload($file, $user_id, $ip, $ua, $expires_opt);
    if ($r['ok']) {
        $results[] = ['url' => $r['url'], 'filename' => $r['filename'], 'bbcode' => '[img]' . $r['url'] . '[/img]', 'html' => '<img src="' . $r['url'] . '">'];
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $r['error']]);
        exit;
    }
}
echo json_encode(['success' => true, 'data' => $results]);
