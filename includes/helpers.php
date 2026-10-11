<?php
/**
 * ============================================================
 * File: includes/helpers.php
 * Deskripsi: Helper umum PixiCDN (sanitize, CSRF, response, filename)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

/**
 * Ambil environment variable dengan fallback.
 *
 * @param string $key Nama variable
 * @param string $default Nilai default
 * @return string Nilai variable
 */
function env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

/**
 * Escape output HTML untuk cegah XSS.
 *
 * @param string $value Input user
 * @return string Output aman
 */
function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Start session aman (httponly, secure, samesite=Lax).
 * Kanonis: semua pemanggil session/CSRF lewat fungsi ini.
 *
 * @return void
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Custom save path agar tidak konflik dengan default PHP-FPM (/tmp)
    // dan konsisten di semua endpoint. Folder di-ignore git.
    $save_path = dirname(__DIR__) . '/tmp-sessions';
    if (!is_dir($save_path)) {
        mkdir($save_path, 0770, true);
    }
    if (is_dir($save_path) && is_writable($save_path)) {
        session_save_path($save_path);
    }
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'PIXICDN_SESSION');
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Ambil atau buat CSRF token sesi.
 *
 * @return string Token CSRF
 */
function csrfToken(): string
{
    start_secure_session();
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Verifikasi CSRF token.
 *
 * @param string $token Token dari form
 * @return bool True jika valid
 */
function verifyCsrf(string $token): bool
{
    start_secure_session();
    $expected = $_SESSION[CSRF_TOKEN_KEY] ?? '';
    if ($expected === '' || $token === '') {
        return false;
    }
    return hash_equals($expected, $token);
}

/**
 * Kirim response JSON dan hentikan eksekusi.
 *
 * @param array<string, mixed> $payload Data response
 * @param int $status HTTP status code
 * @return void
 */
function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Redirect ke URL lain.
 *
 * @param string $url Tujuan redirect
 * @return void
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Cek rate limit sederhana via tabel rate_limits.
 * Memakai getDB() saat runtime — pastikan includes/db.php sudah di-load.
 *
 * @param string $identifier IP atau user id
 * @param string $action Aksi (upload, login, register, api_*)
 * @param int $max Maksimal per window
 * @param int $window_seconds Window detik
 * @return bool True jika masih boleh
 */
function check_rate_limit(string $identifier, string $action, int $max, int $window_seconds = 3600): bool
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT request_count, window_end FROM rate_limits WHERE identifier = ? AND action = ?');
    $stmt->execute([$identifier, $action]);
    $row = $stmt->fetch();
    if (!$row || strtotime((string) $row['window_end']) < time()) {
        $stmt = $pdo->prepare('INSERT INTO rate_limits (identifier, action, request_count, window_start, window_end) VALUES (?, ?, 1, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE request_count = 1, window_start = NOW(), window_end = DATE_ADD(NOW(), INTERVAL ? SECOND)');
        $stmt->execute([$identifier, $action, $window_seconds, $window_seconds]);
        return true;
    }
    if ((int) $row['request_count'] >= $max) {
        return false;
    }
    $pdo->prepare('UPDATE rate_limits SET request_count = request_count + 1 WHERE identifier = ? AND action = ?')->execute([$identifier, $action]);
    return true;
}

/**
 * Generate nama file unik: YYYYMMDD_random.ext
 *
 * @param string $extension Ekstensi tanpa titik
 * @return string Nama file unik
 */
function generate_filename(string $extension): string
{
    $extension = strtolower(trim($extension));
    return date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
}
