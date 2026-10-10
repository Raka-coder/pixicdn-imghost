<?php
/**
 * ============================================================
 * File: includes/album.php
 * Deskripsi: Album CRUD + slug + expiry mapping (Fase 3)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Ubah teks menjadi slug URL (lowercase alnum-dash).
 *
 * @param string $text Nama album
 * @return string Slug (fallback 'album')
 */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text === '' ? 'album' : substr($text, 0, 100);
}

/**
 * Validasi nama album: 1-100 karakter.
 *
 * @param string $name Nama album
 * @return bool True jika valid
 */
function validate_album_name(string $name): bool
{
    $name = trim($name);
    return $name !== '' && mb_strlen($name) <= 100;
}

/**
 * Petakan opsi expiry ke interval SQL (null = never).
 *
 * @param string|null $opt never/1h/1d/7d/30d
 * @return string|null Interval SQL atau null
 */
function expiry_to_sql(?string $opt): ?string
{
    return match ($opt) {
        '1h' => 'INTERVAL 1 HOUR',
        '1d' => 'INTERVAL 1 DAY',
        '7d' => 'INTERVAL 7 DAY',
        '30d' => 'INTERVAL 30 DAY',
        default => null,
    };
}

/**
 * Buat album baru (slug unik per user, tambah -2/-3 dst jika bentrok).
 *
 * @param int $user_id Pemilik
 * @param string $name Nama album
 * @param string|null $description Deskripsi
 * @return int ID album
 * @throws RuntimeException Jika nama invalid
 */
function create_album(int $user_id, string $name, ?string $description = null): int
{
    if (!validate_album_name($name)) {
        throw new RuntimeException('Nama album tidak valid.');
    }
    $base = slugify($name);
    $slug = $base;
    $pdo = getDB();
    for ($i = 2; $i <= 99; $i++) {
        $stmt = $pdo->prepare('SELECT id FROM albums WHERE user_id = ? AND slug = ?');
        $stmt->execute([$user_id, $slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $base . '-' . $i;
    }
    $stmt = $pdo->prepare('INSERT INTO albums (user_id, name, slug, description) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, trim($name), $slug, $description]);
    return (int) $pdo->lastInsertId();
}

/**
 * Ambil album milik user (atau publik jika $allow_public).
 *
 * @param int $album_id ID album
 * @param int $user_id ID user request (0 = anonymous)
 * @param bool $allow_public Izinkan album publik milik orang lain
 * @return array<string, mixed>|null Album atau null
 */
function get_album(int $album_id, int $user_id, bool $allow_public = false): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM albums WHERE id = ? AND is_deleted = FALSE');
    $stmt->execute([$album_id]);
    $album = $stmt->fetch();
    if (!$album) {
        return null;
    }
    if ((int) $album['user_id'] === $user_id) {
        return $album;
    }
    if ($allow_public && (bool) $album['is_public']) {
        return $album;
    }
    return null;
}

/**
 * Tambah gambar ke album (gambar harus milik user yang sama).
 *
 * @param int $album_id Album tujuan
 * @param int $image_id Gambar
 * @param int $user_id Pemilik
 * @return bool True jika sukses
 */
function add_to_album(int $album_id, int $image_id, int $user_id): bool
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id FROM albums WHERE id = ? AND user_id = ? AND is_deleted = FALSE');
    $stmt->execute([$album_id, $user_id]);
    if (!$stmt->fetch()) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT id FROM images WHERE id = ? AND user_id = ? AND is_deleted = FALSE');
    $stmt->execute([$image_id, $user_id]);
    if (!$stmt->fetch()) {
        return false;
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO album_images (album_id, image_id) VALUES (?, ?)');
        $stmt->execute([$album_id, $image_id]);
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return true; // sudah ada → anggap sukses
        }
        throw $e;
    }
    $pdo->prepare('UPDATE albums SET image_count = (SELECT COUNT(*) FROM album_images WHERE album_id = ?) WHERE id = ?')->execute([$album_id, $album_id]);
    return true;
}

/**
 * Keluarkan gambar dari album milik user.
 *
 * @param int $album_id Album
 * @param int $image_id Gambar
 * @param int $user_id Pemilik
 * @return bool True jika sukses
 */
function remove_from_album(int $album_id, int $image_id, int $user_id): bool
{
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id FROM albums WHERE id = ? AND user_id = ? AND is_deleted = FALSE');
    $stmt->execute([$album_id, $user_id]);
    if (!$stmt->fetch()) {
        return false;
    }
    $pdo->prepare('DELETE FROM album_images WHERE album_id = ? AND image_id = ?')->execute([$album_id, $image_id]);
    $pdo->prepare('UPDATE albums SET image_count = (SELECT COUNT(*) FROM album_images WHERE album_id = ?) WHERE id = ?')->execute([$album_id, $album_id]);
    return true;
}
