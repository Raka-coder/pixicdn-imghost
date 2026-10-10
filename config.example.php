<?php
/**
 * ============================================================
 * File: config.example.php
 * Deskripsi: Template konfigurasi PixiCDN - copy ke config.php lalu isi nilai asli
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

// Salin file ini ke config.php:
// copy config.example.php config.php

// ---------- Aplikasi ----------
const APP_NAME = 'PixiCDN';
const APP_URL = 'https://pixicdn.erdtreeproject.my.id';
const APP_DEBUG = true; // set false di production
const APP_TIMEZONE = 'Asia/Jakarta';

// ---------- Database (MariaDB 11, Server 3) ----------
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'pixicdn_db';
const DB_USER = 'pixicdn_user';
const DB_PASS = 'ganti-password-ini';

// ---------- Object Storage (RustFS S3-compatible, Server 3) ----------
// Docs: AWS SDK for PHP S3Client dengan custom endpoint + path-style
const S3_ENDPOINT = 'http://127.0.0.1:9000';
const S3_BUCKET = 'pixicdn';
const S3_REGION = 'us-east-1';
const S3_KEY = 'rustfsadmin';
const S3_SECRET = 'ganti-secret-ini';
const S3_USE_PATH_STYLE = true;

// ---------- Keamanan ----------
const SESSION_NAME = 'PIXICDN_SESSION';
const CSRF_TOKEN_KEY = 'csrf_token';

// ---------- Upload ----------
const MAX_FILE_SIZE = 5242880; // 5 MB
const MAX_FILES_PER_UPLOAD = 5;
const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
