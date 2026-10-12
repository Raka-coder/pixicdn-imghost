<?php

/**
 * ============================================================
 * File: health.php
 * Deskripsi: Health check endpoint untuk monitoring (Uptime Kuma).
 *   200 = ok, 503 = degraded. Tanpa kredensial, tanpa session.
 * Author: Raka Restu Saputra
 * Created: 2026-10-11
 * ============================================================
 */
declare(strict_types=1);

if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php'; // dev-fill saja, guarded defined()
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'checks' => [],
];

// 1. Database check
try {
    getDB()->query('SELECT 1');
    $health['checks']['database'] = 'ok';
} catch (Throwable $e) {
    error_log('Health DB gagal: ' . $e->getMessage());
    $health['status'] = 'degraded';
    $health['checks']['database'] = 'failed';
}

// 2. S3/RustFS check (skip bila SDK tidak terinstall)
try {
    if (!class_exists('Aws\S3\S3Client')) {
        throw new RuntimeException('AWS SDK tidak tersedia');
    }
    require_once __DIR__ . '/includes/s3.php';
    getS3Client()->headBucket(['Bucket' => S3_BUCKET]);
    $health['checks']['storage'] = 'ok';
} catch (Throwable $e) {
    error_log('Health S3 gagal: ' . $e->getMessage());
    $health['status'] = 'degraded';
    $health['checks']['storage'] = 'failed';
}

// 3. Disk space check
$free = disk_free_space('/');
$total = disk_total_space('/');
if ($free !== false && $total !== false && $total > 0) {
    $usage = ($total - $free) / $total * 100;
    $health['checks']['disk_usage'] = round($usage, 2) . '%';
    if ($usage > 90) {
        $health['status'] = 'degraded';
    }
} else {
    $health['checks']['disk_usage'] = 'unknown';
}

http_response_code($health['status'] === 'ok' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
