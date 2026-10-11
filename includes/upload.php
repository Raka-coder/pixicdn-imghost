<?php
/**
 * ============================================================
 * File: includes/upload.php
 * Deskripsi: Validasi + penyimpanan gambar PixiCDN (Tahap 3)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/s3.php';
require_once __DIR__ . '/thumbnail.php';

const UPLOAD_MIME_MAP = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
];

/**
 * Validasi file upload (ext + error + size). MIME final dicek via getimagesize/finfo saat handle.
 *
 * @param array<string, mixed> $file Entri $_FILES
 * @return string|null Null jika valid, pesan Indonesia jika tidak
 */
function validate_image_file(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'File gagal diupload. Silakan coba lagi.';
    }
    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return 'Format tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.';
    }
    if ((int) ($file['size'] ?? 0) > MAX_FILE_SIZE) {
        return 'Ukuran file melebihi 5 MB.';
    }
    if ((int) ($file['size'] ?? 0) <= 0) {
        return 'File kosong.';
    }
    return null;
}

/**
 * Bangun S3 key: uploads/YYYY/MM/{filename}.
 *
 * @param string $filename Nama file unik
 * @return string S3 key
 */
function build_s3_key(string $filename): string
{
    return sprintf('uploads/%s/%s/%s', date('Y'), date('m'), $filename);
}

/**
 * Simpan metadata gambar ke MariaDB.
 *
 * @param array<string, mixed> $meta Metadata (filename, original_name, s3_key, url, mime, ext, size, w, h, user_id, ip, ua)
 * @return int ID gambar
 */
function save_image_record(array $meta): int
{
    $stmt = getDB()->prepare('INSERT INTO images (user_id, filename, original_name, s3_key, public_url, thumbnail_url, mime_type, extension, size, width, height, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $meta['user_id'], $meta['filename'], $meta['original_name'], $meta['s3_key'],
        $meta['public_url'], $meta['thumbnail_url'] ?? null, $meta['mime_type'], $meta['extension'], $meta['size'],
        $meta['width'], $meta['height'], $meta['ip_address'], $meta['user_agent'],
    ]);
    return (int) getDB()->lastInsertId();
}

/**
 * Proses satu file: validasi penuh + S3 + DB.
 *
 * @param array<string, mixed> $file Entri $_FILES
 * @param int|null $user_id ID user atau null anonymous
 * @param string $ip IP uploader
 * @param string $user_agent UA uploader
 * @param string|null $expires_opt never/1h/1d/7d/30d
 * @return array{ok: bool, url?: string, filename?: string, error?: string} Hasil
 */
function handle_upload(array $file, ?int $user_id, string $ip, string $user_agent, ?string $expires_opt = null): array
{
    $err = validate_image_file($file);
    if ($err !== null) {
        return ['ok' => false, 'error' => $err];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $info = @getimagesize($tmp);
    if ($info === false) {
        return ['ok' => false, 'error' => 'File bukan gambar valid.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $expected = UPLOAD_MIME_MAP[$ext] ?? '';
    if ($expected === '' || ($mime !== $expected && !str_starts_with($mime, 'image/'))) {
        return ['ok' => false, 'error' => 'Tipe file tidak sesuai ekstensi.'];
    }
    $filename = generate_filename($ext);
    $s3_key = build_s3_key($filename);
    if (!s3_put($tmp, $s3_key)) {
        return ['ok' => false, 'error' => 'Gagal menyimpan ke storage. Coba lagi.'];
    }
    $url = rtrim(APP_URL, '/') . '/' . $filename;
    $thumb_url = upload_thumbnail($tmp, $filename);
    try {
        $id = save_image_record([
            'user_id' => $user_id, 'filename' => $filename, 'original_name' => (string) $file['name'],
            's3_key' => $s3_key, 'public_url' => $url, 'thumbnail_url' => $thumb_url, 'mime_type' => $mime, 'extension' => $ext,
            'size' => (int) $file['size'], 'width' => $info[0] ?? null, 'height' => $info[1] ?? null,
            'ip_address' => $ip, 'user_agent' => mb_substr($user_agent, 0, 1000),
        ]);
    } catch (PDOException $e) {
        error_log('DB insert gambar gagal: ' . $e->getMessage());
        s3_delete($s3_key);
        return ['ok' => false, 'error' => 'Gagal menyimpan metadata. Coba lagi.'];
    }
    if ($user_id !== null) {
        getDB()->prepare('UPDATE users SET storage_used = storage_used + ? WHERE id = ?')->execute([(int) $file['size'], $user_id]);
    }
    $interval = match ($expires_opt) {
        '1h' => 'INTERVAL 1 HOUR',
        '1d' => 'INTERVAL 1 DAY',
        '7d' => 'INTERVAL 7 DAY',
        '30d' => 'INTERVAL 30 DAY',
        default => null,
    };
    if ($interval !== null) {
        getDB()->query("UPDATE images SET expires_at = DATE_ADD(NOW(), $interval) WHERE id = $id");
    }
    start_secure_session();
    $_SESSION['owned_images'] = array_slice(array_merge($_SESSION['owned_images'] ?? [], [$id]), -50);
    return ['ok' => true, 'url' => $url, 'filename' => $filename];
}
