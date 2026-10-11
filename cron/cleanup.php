<?php
/**
 * ============================================================
 * File: cron/cleanup.php
 * Deskripsi: Cleanup CLI (expired, fisik >30 hari, token, view/api logs)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * Jalankan: php cron/cleanup.php (cron harian / systemd timer)
 * ============================================================
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/s3.php';

$pdo = getDB();

// 1. Expired → soft-delete
$expired = (int) $pdo->exec("UPDATE images SET is_deleted = TRUE, deleted_at = NOW() WHERE is_deleted = FALSE AND expires_at IS NOT NULL AND expires_at < NOW()");
echo "expired_soft_deleted=$expired" . PHP_EOL;

// 2. Fisik soft-deleted >30 hari (batch 100)
$stmt = $pdo->query('SELECT id, s3_key FROM images WHERE is_deleted = TRUE AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY) LIMIT 100');
$rows = $stmt->fetchAll();
$purged = 0;
foreach ($rows as $row) {
    if (s3_delete((string) $row['s3_key'])) {
        $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([(int) $row['id']]);
        $purged++;
    } else {
        error_log('Cleanup S3 gagal id=' . $row['id']);
    }
}
echo "purged=$purged" . PHP_EOL;

// 3. Token reset expired/used
$tokens = $pdo->exec('DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL');
echo "tokens_cleaned=$tokens" . PHP_EOL;

// 4. view_logs >90 hari (batch 1000)
$views = $pdo->exec('DELETE FROM view_logs WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 90 DAY) LIMIT 1000');
echo "view_logs_cleaned=$views" . PHP_EOL;

// 5. api_logs >90 hari (batch 1000)
$api = $pdo->exec('DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) LIMIT 1000');
echo "api_logs_cleaned=$api" . PHP_EOL;

// 6. rate_limits window kedaluwarsa
$rates = $pdo->exec('DELETE FROM rate_limits WHERE window_end < NOW()');
echo "rate_limits_cleaned=$rates" . PHP_EOL;

echo "OK cleanup" . PHP_EOL;
