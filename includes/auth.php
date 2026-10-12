<?php

/**
 * ============================================================
 * File: includes/auth.php
 * Deskripsi: Auth core PixiCDN (register, login, recovery code reset)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Validasi username: 3-30 karakter, huruf/angka/underscore.
 *
 * @param string $username Username
 * @return bool True jika valid
 */
function validate_username(string $username): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username);
}

/**
 * Validasi password: minimal 8 karakter.
 *
 * @param string $password Password plain
 * @return bool True jika valid
 */
function validate_password(string $password): bool
{
    return strlen($password) >= 8;
}

/**
 * Generate recovery code 8 char (A-Z2-9 tanpa O0I1 ambigu).
 *
 * @return string Recovery code
 */
function generate_recovery_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $code;
}

/**
 * Ambil user login saat ini.
 *
 * @return array<string, mixed>|null Data user atau null
 */
function current_user(): ?array
{
    start_secure_session();
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id <= 0) {
        return null;
    }
    $stmt = getDB()->prepare('SELECT id, username, storage_quota, storage_used, created_at FROM users WHERE id = ? AND is_active = TRUE');
    $stmt->execute([(int) $user_id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Wajibkan login, redirect ke login jika belum.
 *
 * @return void
 */
function require_login(): void
{
    if (current_user() === null) {
        redirect('/auth/login.php');
    }
}

/**
 * Daftarkan user baru + recovery code (tampil sekali ke caller).
 *
 * @param string $username Username
 * @param string $password Password plain
 * @return array{user_id: int, recovery_code: string} ID + code plain
 * @throws RuntimeException Jika username dipakai / validasi gagal
 */
function register_user(string $username, string $password): array
{
    if (!validate_username($username) || !validate_password($password)) {
        throw new RuntimeException('Username atau password tidak valid');
    }
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new RuntimeException('Username sudah dipakai');
    }
    $recovery_code = generate_recovery_code();
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, recovery_code_hash) VALUES (?, ?, ?)');
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        password_hash($recovery_code, PASSWORD_BCRYPT, ['cost' => 12]),
    ]);
    return ['user_id' => (int) $pdo->lastInsertId(), 'recovery_code' => $recovery_code];
}

/**
 * Verifikasi kredensial login.
 *
 * @param string $username Username
 * @param string $password Password plain
 * @return array<string, mixed>|null Data user jika cocok
 */
function verify_login(string $username, string $password): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE username = ? AND is_active = TRUE');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    return $user;
}

/**
 * Tandai login sukses: regenerate session + update last_login.
 *
 * @param int $user_id ID user
 * @return void
 */
function mark_login(int $user_id): void
{
    start_secure_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $stmt = getDB()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
    $stmt->execute([$user_id]);
}

/**
 * Request reset via recovery code: buat token 1 jam jika code cocok.
 * Response selalu sama (no enumeration dilakukan di halaman).
 *
 * @param string $username Username
 * @param string $recovery_code Code plain dari user
 * @param string $ip IP request
 * @return bool True jika token dibuat (jangan bocorkan ke UI bedanya)
 */
function request_password_reset(string $username, string $recovery_code, string $ip): bool
{
    $stmt = getDB()->prepare('SELECT id, recovery_code_hash FROM users WHERE username = ? AND is_active = TRUE');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || empty($user['recovery_code_hash']) || !password_verify($recovery_code, $user['recovery_code_hash'])) {
        return false;
    }
    $token = bin2hex(random_bytes(32));
    $stmt = getDB()->prepare("INSERT INTO password_resets (user_id, token_hash, method, ip_address, expires_at) VALUES (?, ?, 'recovery_code', ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $stmt->execute([(int) $user['id'], hash('sha256', $token), $ip]);
    start_secure_session();
    $_SESSION['reset_token_plain'] = $token;
    $_SESSION['reset_user_id'] = (int) $user['id'];
    return true;
}

/**
 * Reset password dengan token plain dari sesi.
 *
 * @param string $token_plain Token plain
 * @param string $new_password Password baru
 * @return bool True jika sukses
 */
function reset_password_with_token(string $token_plain, string $new_password): bool
{
    if (!validate_password($new_password) || $token_plain === '') {
        return false;
    }
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL');
    $stmt->execute([hash('sha256', $token_plain)]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([
        password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]),
        (int) $row['user_id'],
    ]);
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
    return true;
}
