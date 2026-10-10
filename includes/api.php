<?php
/**
 * ============================================================
 * File: includes/api.php
 * Deskripsi: API key management + JSON envelope + api_logs (Tahap 4)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Generate API key 64 hex (256-bit CSPRNG).
 *
 * @return string API key
 */
function generate_api_key(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Validasi API key → user aktif.
 *
 * @param string $api_key Key dari header X-API-Key
 * @return array<string, mixed>|null User jika valid
 */
function validate_api_key(string $api_key): ?array
{
    if ($api_key === '') {
        return null;
    }
    $stmt = getDB()->prepare('SELECT * FROM users WHERE api_key = ? AND is_active = TRUE');
    $stmt->execute([$api_key]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Buat/rotasi API key user, kembalikan plain (tampilkan sekali saja).
 *
 * @param int $user_id ID user
 * @return string Key plain baru
 */
function rotate_api_key(int $user_id): string
{
    $key = generate_api_key();
    getDB()->prepare('UPDATE users SET api_key = ?, api_key_created_at = NOW() WHERE id = ?')->execute([$key, $user_id]);
    return $key;
}

/**
 * Cabut API key user.
 *
 * @param int $user_id ID user
 * @return void
 */
function revoke_api_key(int $user_id): void
{
    getDB()->prepare('UPDATE users SET api_key = NULL, api_key_created_at = NULL WHERE id = ?')->execute([$user_id]);
}

/**
 * Kirim JSON sukses + exit (envelope rules §7.3).
 *
 * @param array<string, mixed> $data Payload data
 * @param int $status HTTP code
 * @return void
 */
function api_success(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'data' => $data,
        'meta' => ['timestamp' => date(DATE_ATOM)],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Kirim JSON error + exit.
 *
 * @param string $code Kode error (mis. AUTH_INVALID)
 * @param string $message Pesan Indonesia
 * @param int $status HTTP code
 * @param array<string, mixed> $details Detail tambahan
 * @return void
 */
function api_error(string $code, string $message, int $status = 400, array $details = []): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => ['code' => $code, 'message' => $message, 'details' => $details],
        'meta' => ['timestamp' => date(DATE_ATOM)],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Catat request API ke api_logs (best-effort).
 *
 * @param int|null $user_id User atau null
 * @param string $endpoint Endpoint
 * @param string $method HTTP method
 * @param int $status_code Status response
 * @return void
 */
function log_api(?int $user_id, string $endpoint, string $method, int $status_code): void
{
    try {
        getDB()->prepare('INSERT INTO api_logs (user_id, endpoint, method, status_code, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)')->execute([
            $user_id, mb_substr($endpoint, 0, 100), $method, $status_code,
            $_SERVER['REMOTE_ADDR'] ?? null, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
        ]);
    } catch (PDOException $e) {
        error_log('api_logs gagal: ' . $e->getMessage());
    }
}

/**
 * Ambil user dari header X-API-Key, atau kirim 401.
 *
 * @return array<string, mixed> User valid
 */
function require_api_key(): array
{
    $key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    $user = validate_api_key((string) $key);
    if ($user === null) {
        log_api(null, $_SERVER['REQUEST_URI'] ?? '', $_SERVER['REQUEST_METHOD'] ?? '', 401);
        api_error('AUTH_INVALID', 'API key tidak valid.', 401);
    }
    return $user;
}
