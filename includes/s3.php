<?php
/**
 * ============================================================
 * File: includes/s3.php
 * Deskripsi: Helper AWS SDK for PHP untuk RustFS (S3-compatible)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * Buat S3Client untuk RustFS (custom endpoint + path-style).
 * Referensi Context7 /aws/aws-sdk-php: endpoint override + putObject.
 *
 * @return S3Client Client S3
 */
function getS3Client(): S3Client
{
    static $client = null;

    if ($client instanceof S3Client) {
        return $client;
    }

    if (is_file(dirname(__DIR__) . '/config.php')) {
        require_once dirname(__DIR__) . '/config.php';
    }
    require_once dirname(__DIR__) . '/config.example.php'; // dev-fill saja, guarded defined()

    $client = new S3Client([
        'version' => 'latest',
        'region' => defined('S3_REGION') ? S3_REGION : 'us-east-1',
        'endpoint' => S3_ENDPOINT,
        'use_path_style_endpoint' => defined('S3_USE_PATH_STYLE') ? S3_USE_PATH_STYLE : true,
        'credentials' => [
            'key' => S3_KEY,
            'secret' => S3_SECRET,
        ],
    ]);

    return $client;
}

/**
 * Upload file lokal ke bucket.
 *
 * @param string $local_path Path file lokal
 * @param string $s3_key Key tujuan di bucket
 * @return bool True jika sukses
 */
function s3_put(string $local_path, string $s3_key): bool
{
    try {
        getS3Client()->putObject([
            'Bucket' => S3_BUCKET,
            'Key' => $s3_key,
            'Body' => fopen($local_path, 'r'),
        ]);
        return true;
    } catch (S3Exception $e) {
        error_log('S3 put gagal: ' . $e->getMessage());
        return false;
    }
}

/**
 * Hapus object dari bucket.
 *
 * @param string $s3_key Key di bucket
 * @return bool True jika sukses
 */
function s3_delete(string $s3_key): bool
{
    try {
        getS3Client()->deleteObject([
            'Bucket' => S3_BUCKET,
            'Key' => $s3_key,
        ]);
        return true;
    } catch (S3Exception $e) {
        error_log('S3 delete gagal: ' . $e->getMessage());
        return false;
    }
}

/**
 * Cek apakah object ada di bucket.
 *
 * @param string $s3_key Key di bucket
 * @return bool True jika ada
 */
function s3_exists(string $s3_key): bool
{
    try {
        getS3Client()->headObject([
            'Bucket' => S3_BUCKET,
            'Key' => $s3_key,
        ]);
        return true;
    } catch (S3Exception $e) {
        return false;
    }
}
