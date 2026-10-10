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
 * Ambil atau buat CSRF token sesi.
 *
 * @return string Token CSRF
 */
function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
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
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
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
