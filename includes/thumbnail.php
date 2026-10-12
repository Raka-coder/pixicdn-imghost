<?php

/**
 * ============================================================
 * File: includes/thumbnail.php
 * Deskripsi: Thumbnail GD 300px max (graceful-null jika GD absen)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/s3.php';

/**
 * Hitung dimensi thumb: skala sisi panjang ke $max, tanpa upscale.
 *
 * @param int $width Lebar asli
 * @param int $height Tinggi asli
 * @param int $max Maksimal sisi panjang
 * @return array{int, int} [lebar, tinggi] baru
 */
function thumb_dimensions(int $width, int $height, int $max = 300): array
{
    if ($width <= 0 || $height <= 0) {
        return [0, 0];
    }
    $longest = max($width, $height);
    if ($longest <= $max) {
        return [$width, $height];
    }
    $scale = $max / $longest;
    return [(int) round($width * $scale), (int) round($height * $scale)];
}

/**
 * Buat thumbnail dari file gambar ke path tujuan (JPEG q80).
 * GIF animasi → frame pertama saja. Return false jika GD absen/gagal.
 *
 * @param string $src_tmp Path sumber
 * @param string $dst_tmp Path tujuan
 * @param int $max Maksimal sisi panjang
 * @return bool True jika sukses
 */
function make_thumbnail(string $src_tmp, string $dst_tmp, int $max = 300): bool
{
    if (!extension_loaded('gd')) {
        error_log('Thumbnail skip: ext-gd tidak tersedia (thumbnail_status=null)');
        return false;
    }
    $info = @getimagesize($src_tmp);
    if ($info === false) {
        return false;
    }
    [$src_w, $src_h] = [$info[0], $info[1]];
    [$dst_w, $dst_h] = thumb_dimensions($src_w, $src_h, $max);
    $mime = $info['mime'] ?? '';
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($src_tmp),
        'image/png' => @imagecreatefrompng($src_tmp),
        'image/gif' => @imagecreatefromgif($src_tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src_tmp) : false,
        default => false,
    };
    if ($src === false) {
        return false;
    }
    $dst = imagecreatetruecolor($dst_w, $dst_h);
    if ($dst === false) {
        imagedestroy($src);
        return false;
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
    $ok = imagejpeg($dst, $dst_tmp, 80);
    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}

/**
 * Buat + upload thumbnail ke S3, kembalikan public URL atau null.
 *
 * @param string $src_tmp Path file asli lokal
 * @param string $filename Nama file original (untuk prefix thumb_)
 * @return string|null URL thumbnail atau null jika gagal/skip
 */
function upload_thumbnail(string $src_tmp, string $filename): ?string
{
    $thumb_name = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $s3_key = sprintf('thumbnails/%s/%s/%s', date('Y'), date('m'), $thumb_name);
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $thumb_name;
    if (!make_thumbnail($src_tmp, $tmp)) {
        return null;
    }
    try {
        $ok = s3_put($tmp, $s3_key);
    } finally {
        @unlink($tmp);
    }
    if (!$ok) {
        return null;
    }
    return rtrim(APP_URL, '/') . '/' . $thumb_name;
}
