<?php
/**
 * ============================================================
 * File: config.example.php
 * Deskripsi: Template konfigurasi PixiCDN — TIDAK berisi kredensial asli.
 *   Cara pakai: salin ke config.php lalu isi nilai asli, atau set
 *   environment variable (nama sama dengan konstanta). Nilai di
 *   config.php / environment selalu menang karena guards defined().
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

// Salin file ini ke config.php:
// copy config.example.php config.php

// ---------- Aplikasi ----------
defined('APP_NAME') || define('APP_NAME', 'PixiCDN');
defined('APP_URL') || define('APP_URL', getenv('APP_URL') ?: 'CHANGE_ME');
defined('APP_DEBUG') || define('APP_DEBUG', false);
defined('APP_TIMEZONE') || define('APP_TIMEZONE', 'Asia/Jakarta');

// ---------- Database (MariaDB 11, Server 3) ----------
defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'CHANGE_ME');
defined('DB_PORT') || define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'CHANGE_ME');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: 'CHANGE_ME');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: 'CHANGE_ME');

// ---------- Object Storage (RustFS S3-compatible, Server 3) ----------
defined('S3_ENDPOINT') || define('S3_ENDPOINT', getenv('S3_ENDPOINT') ?: 'CHANGE_ME');
defined('S3_BUCKET') || define('S3_BUCKET', getenv('S3_BUCKET') ?: 'CHANGE_ME');
defined('S3_REGION') || define('S3_REGION', getenv('S3_REGION') ?: 'us-east-1');
defined('S3_KEY') || define('S3_KEY', getenv('S3_KEY') ?: 'CHANGE_ME');
defined('S3_SECRET') || define('S3_SECRET', getenv('S3_SECRET') ?: 'CHANGE_ME');
defined('S3_USE_PATH_STYLE') || define('S3_USE_PATH_STYLE', true);
defined('S3_PUBLIC_URL') || define('S3_PUBLIC_URL', getenv('S3_PUBLIC_URL') ?: getenv('APP_URL') ?: 'CHANGE_ME');

// ---------- Keamanan ----------
defined('SESSION_NAME') || define('SESSION_NAME', 'PIXICDN_SESSION');
defined('CSRF_TOKEN_KEY') || define('CSRF_TOKEN_KEY', 'csrf_token');

// ---------- Upload ----------
defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 5242880); // 5 MB
defined('MAX_FILES_PER_UPLOAD') || define('MAX_FILES_PER_UPLOAD', 5);
defined('ALLOWED_EXTENSIONS') || define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
