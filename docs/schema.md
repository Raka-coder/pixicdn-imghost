
# 🗄️ Skema Database Lengkap — PixiCDN

**Versi:** 1.0
**Database:** `pixicdn_db`
**DBMS:** MariaDB 11
**Character Set:** `utf8mb4`
**Collation:** `utf8mb4_unicode_ci`
**Engine:** InnoDB

---

## 📋 Daftar Isi

1. [Entity Relationship Diagram (ERD)](#1-entity-relationship-diagram-erd)
2. [Tabel Core](#2-tabel-core)
   - [2.1 users](#21-tabel-users)
   - [2.2 images](#22-tabel-images)
   - [2.3 password_resets](#23-tabel-password_resets)
3. [Tabel Analytics](#3-tabel-analytics)
   - [3.1 view_logs](#31-tabel-view_logs)
   - [3.2 api_logs](#32-tabel-api_logs)
4. [Tabel Album (Fase 3)](#4-tabel-album-fase-3)
   - [4.1 albums](#41-tabel-albums)
   - [4.2 album_images](#42-tabel-album_images)
5. [Tabel Keamanan](#5-tabel-keamanan)
   - [5.1 rate_limits](#51-tabel-rate_limits)
   - [5.2 audit_logs](#52-tabel-audit_logs)
6. [Ringkasan Relasi](#6-ringkasan-relasi)
7. [Migration SQL Lengkap](#7-migration-sql-lengkap)
8. [Sample Data](#8-sample-data)
9. [Query Umum](#9-query-umum)

---

## 1. Entity Relationship Diagram (ERD)

```
┌─────────────────┐
│     users       │
│─────────────────│
│ id (PK)         │
│ username (UQ)   │
│ password_hash   │
│ recovery_code_  │
│   hash          │
│ api_key (UQ)    │
│ storage_quota   │
│ storage_used    │
│ role            │
│ is_active       │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N
         │
    ┌────┴────┬──────────┬──────────┬──────────┐
    │         │          │          │          │
    ▼         ▼          ▼          ▼          ▼
┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐
│ images │ │passwrd_│ │view_   │ │api_    │ │albums  │
│        │ │resets  │ │logs    │ │logs    │ │        │
└────────┘ └────────┘ └────────┘ └────────┘ └───┬────┘
                                                 │
                                                 │ M:N
                                                 ▼
                                          ┌──────────────┐
                                          │ album_images │
                                          └──────────────┘
```

**Relasi:**

| Dari | Ke | Tipe | On Delete |
|:---|:---|:---|:---|
| `images.user_id` | `users.id` | N:1 | SET NULL |
| `password_resets.user_id` | `users.id` | N:1 | CASCADE |
| `view_logs.image_id` | `images.id` | N:1 | CASCADE |
| `api_logs.user_id` | `users.id` | N:1 | SET NULL |
| `albums.user_id` | `users.id` | N:1 | CASCADE |
| `album_images.album_id` | `albums.id` | N:1 | CASCADE |
| `album_images.image_id` | `images.id` | N:1 | CASCADE |

---

## 2. Tabel Core

### 2.1 Tabel `users`

Menyimpan data user (akun, kredensial, quota storage).

```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Kredensial
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    recovery_code_hash VARCHAR(255) NULL,
    recovery_code_used_at TIMESTAMP NULL,
    api_key VARCHAR(64) NULL UNIQUE,
    api_key_created_at TIMESTAMP NULL,
    
    -- Storage
    storage_quota BIGINT UNSIGNED DEFAULT 104857600,    -- 100 MB
    storage_used BIGINT UNSIGNED DEFAULT 0,
    
    -- Profile (opsional, untuk pengembangan)
    display_name VARCHAR(100) NULL,
    avatar_url VARCHAR(500) NULL,
    bio TEXT NULL,
    
    -- Status
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_username (username),
    INDEX idx_api_key (api_key),
    INDEX idx_role (role),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Kolom Detail:**

| Kolom | Tipe | Deskripsi | Contoh |
|:---|:---|:---|:---|
| `id` | INT UNSIGNED | Primary key | `1` |
| `username` | VARCHAR(50) | Username unik | `raka_restu` |
| `password_hash` | VARCHAR(255) | Hash bcrypt password | `$2y$12$...` |
| `recovery_code_hash` | VARCHAR(255) | Hash bcrypt recovery code | `$2y$12$...` |
| `recovery_code_used_at` | TIMESTAMP | Kapan code dipakai | `2026-10-10 14:00:00` |
| `api_key` | VARCHAR(64) | API key (64 char hex) | `a3f8c2...` |
| `api_key_created_at` | TIMESTAMP | Kapan API key dibuat | `2026-10-10 14:00:00` |
| `storage_quota` | BIGINT | Quota storage (bytes) | `104857600` (100 MB) |
| `storage_used` | BIGINT | Storage terpakai (bytes) | `5242880` (5 MB) |
| `display_name` | VARCHAR(100) | Nama tampilan | `Raka R.` |
| `avatar_url` | VARCHAR(500) | URL avatar | `https://...` |
| `bio` | TEXT | Bio user | `Full-stack developer` |
| `role` | ENUM | Role user | `user` / `admin` |
| `is_active` | BOOLEAN | Status akun | `TRUE` |
| `last_login_at` | TIMESTAMP | Login terakhir | `2026-10-10 14:00:00` |

---

### 2.2 Tabel `images`

Menyimpan metadata gambar yang di-upload.

```sql
CREATE TABLE images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Owner (NULL = anonymous)
    user_id INT UNSIGNED NULL,
    
    -- File info
    filename VARCHAR(255) NOT NULL,                     -- Nama unik di RustFS
    original_name VARCHAR(255) NULL,                    -- Nama asli dari user
    s3_key VARCHAR(500) NOT NULL,                       -- Path di RustFS
    public_url VARCHAR(500) NOT NULL,                   -- URL publik
    thumbnail_url VARCHAR(500) NULL,                    -- URL thumbnail
    mime_type VARCHAR(50) NOT NULL,                     -- image/jpeg, dll
    extension VARCHAR(10) NOT NULL,                     -- jpg, png, dll
    
    -- Ukuran & dimensi
    size INT UNSIGNED NOT NULL,                         -- Ukuran file (bytes)
    width INT UNSIGNED NULL,                            -- Lebar (px)
    height INT UNSIGNED NULL,                           -- Tinggi (px)
    
    -- Analytics
    views INT UNSIGNED DEFAULT 0,                       -- Jumlah view
    downloads INT UNSIGNED DEFAULT 0,                   -- Jumlah download
    
    -- Metadata
    ip_address VARCHAR(45) NULL,                        -- IP uploader (IPv4/IPv6)
    user_agent TEXT NULL,                               -- User agent uploader
    
    -- Status
    is_public BOOLEAN DEFAULT TRUE,                     -- Bisa diakses publik
    is_deleted BOOLEAN DEFAULT FALSE,                   -- Soft delete
    expires_at TIMESTAMP NULL,                          -- Auto-delete (NULL = never)
    
    -- Timestamps
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    -- Foreign Key
    CONSTRAINT fk_images_user 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_filename (filename),
    INDEX idx_user (user_id),
    INDEX idx_uploaded (uploaded_at),
    INDEX idx_expires (expires_at),
    INDEX idx_public (is_public),
    INDEX idx_deleted (is_deleted),
    INDEX idx_user_uploaded (user_id, uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Kolom Detail:**

| Kolom | Tipe | Deskripsi | Contoh |
|:---|:---|:---|:---|
| `id` | INT UNSIGNED | Primary key | `1` |
| `user_id` | INT UNSIGNED | Owner (NULL = anonymous) | `1` / `NULL` |
| `filename` | VARCHAR(255) | Nama file unik di RustFS | `20261010_abc123.jpg` |
| `original_name` | VARCHAR(255) | Nama asli | `screenshot.png` |
| `s3_key` | VARCHAR(500) | Path di RustFS | `uploads/2026/10/abc123.jpg` |
| `public_url` | VARCHAR(500) | URL publik | `https://pixicdn.../abc123.jpg` |
| `thumbnail_url` | VARCHAR(500) | URL thumbnail | `https://pixicdn.../thumb_abc.jpg` |
| `mime_type` | VARCHAR(50) | MIME type | `image/jpeg` |
| `extension` | VARCHAR(10) | Ekstensi file | `jpg` |
| `size` | INT UNSIGNED | Ukuran (bytes) | `245760` |
| `width` | INT UNSIGNED | Lebar (px) | `1920` |
| `height` | INT UNSIGNED | Tinggi (px) | `1080` |
| `views` | INT UNSIGNED | Jumlah view | `42` |
| `downloads` | INT UNSIGNED | Jumlah download | `5` |
| `ip_address` | VARCHAR(45) | IP uploader | `192.168.1.1` |
| `user_agent` | TEXT | User agent | `Mozilla/5.0...` |
| `is_public` | BOOLEAN | Publik atau privat | `TRUE` |
| `is_deleted` | BOOLEAN | Soft delete | `FALSE` |
| `expires_at` | TIMESTAMP | Auto-delete | `NULL` |
| `uploaded_at` | TIMESTAMP | Waktu upload | `2026-10-10 14:00:00` |
| `updated_at` | TIMESTAMP | Waktu update | `2026-10-10 14:00:00` |
| `deleted_at` | TIMESTAMP | Waktu delete | `NULL` |

**Catatan Soft Delete:**

- `is_deleted = TRUE` → gambar dianggap terhapus, tapi file di RustFS masih ada
- Cron job bisa hapus file fisik setelah X hari
- Query harus selalu `WHERE is_deleted = FALSE` untuk menampilkan gambar aktif

---

### 2.3 Tabel `password_resets`

Menyimpan token reset password (untuk recovery code atau email).

```sql
CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Owner
    user_id INT UNSIGNED NOT NULL,
    
    -- Token
    token_hash CHAR(64) NOT NULL,                       -- SHA-256 hash dari token
    method ENUM('recovery_code', 'email', 'admin') NOT NULL DEFAULT 'recovery_code',
    
    -- Metadata
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Status
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    CONSTRAINT fk_resets_user 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_token_hash (token_hash),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    INDEX idx_used (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Kolom Detail:**

| Kolom | Tipe | Deskripsi |
|:---|:---|:---|
| `id` | INT UNSIGNED | Primary key |
| `user_id` | INT UNSIGNED | User yang request reset |
| `token_hash` | CHAR(64) | SHA-256 hash (64 hex char) |
| `method` | ENUM | Metode reset |
| `ip_address` | VARCHAR(45) | IP yang request |
| `user_agent` | TEXT | User agent |
| `expires_at` | TIMESTAMP | Expire (1 jam dari created) |
| `used_at` | TIMESTAMP | Kapan token dipakai (NULL = belum) |
| `created_at` | TIMESTAMP | Waktu request |

**Catatan:** Token disimpan sebagai **SHA-256 hash**, bukan bcrypt, karena token di-generate random (64 char) sehingga tidak perlu lambat seperti password.

---

## 3. Tabel Analytics

### 3.1 Tabel `view_logs`

Menyimpan log view gambar untuk analytics.

```sql
CREATE TABLE view_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Image
    image_id INT UNSIGNED NOT NULL,
    
    -- Visitor
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer VARCHAR(500) NULL,
    
    -- Metadata
    country VARCHAR(2) NULL,                            -- Kode negara ISO (ID, US)
    device_type ENUM('desktop', 'mobile', 'tablet', 'bot', 'unknown') DEFAULT 'unknown',
    
    -- Timestamps
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    CONSTRAINT fk_views_image 
        FOREIGN KEY (image_id) REFERENCES images(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_image (image_id),
    INDEX idx_viewed (viewed_at),
    INDEX idx_ip (ip_address),
    INDEX idx_image_viewed (image_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Catatan:** Tabel ini bisa **besar** (juta row). Untuk production, pertimbangkan:
- Partitioning by `viewed_at` (bulanan)
- Archive setelah 90 hari
- Atau gunakan agregasi (view count per hari)

---

### 3.2 Tabel `api_logs`

Menyimpan log request API untuk monitoring.

```sql
CREATE TABLE api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- User
    user_id INT UNSIGNED NULL,
    
    -- Request
    endpoint VARCHAR(100) NOT NULL,                     -- /api/upload
    method VARCHAR(10) NOT NULL,                        -- GET, POST, DELETE
    status_code SMALLINT UNSIGNED NOT NULL,             -- 200, 401, 500
    
    -- Client
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Performance
    response_time_ms INT UNSIGNED NULL,
    request_size INT UNSIGNED NULL,
    response_size INT UNSIGNED NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    CONSTRAINT fk_api_user 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_user (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_status (status_code),
    INDEX idx_created (created_at),
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Tabel Album (Fase 3)

### 4.1 Tabel `albums`

Menyimpan album untuk mengelompokkan gambar.

```sql
CREATE TABLE albums (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Owner
    user_id INT UNSIGNED NOT NULL,
    
    -- Info
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT NULL,
    cover_image_id INT UNSIGNED NULL,                   -- Gambar sampul
    
    -- Status
    is_public BOOLEAN DEFAULT TRUE,
    is_deleted BOOLEAN DEFAULT FALSE,
    
    -- Stats
    image_count INT UNSIGNED DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Key
    CONSTRAINT fk_albums_user 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_albums_cover 
        FOREIGN KEY (cover_image_id) REFERENCES images(id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    UNIQUE KEY uk_user_slug (user_id, slug),
    INDEX idx_user (user_id),
    INDEX idx_public (is_public),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4.2 Tabel `album_images`

Pivot table untuk relasi M:N antara album dan gambar.

```sql
CREATE TABLE album_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Relasi
    album_id INT UNSIGNED NOT NULL,
    image_id INT UNSIGNED NOT NULL,
    
    -- Metadata
    position INT UNSIGNED DEFAULT 0,                    -- Urutan dalam album
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    CONSTRAINT fk_ai_album 
        FOREIGN KEY (album_id) REFERENCES albums(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ai_image 
        FOREIGN KEY (image_id) REFERENCES images(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes
    UNIQUE KEY uk_album_image (album_id, image_id),
    INDEX idx_album (album_id),
    INDEX idx_image (image_id),
    INDEX idx_position (album_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 5. Tabel Keamanan

### 5.1 Tabel `rate_limits`

Untuk rate limiting (mencegah spam upload/API).

```sql
CREATE TABLE rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Identifier
    identifier VARCHAR(255) NOT NULL,                   -- IP atau user_id atau api_key
    action VARCHAR(50) NOT NULL,                        -- upload, api, login
    
    -- Count
    request_count INT UNSIGNED DEFAULT 1,
    
    -- Window
    window_start TIMESTAMP NOT NULL,
    window_end TIMESTAMP NOT NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    UNIQUE KEY uk_identifier_action (identifier, action),
    INDEX idx_window (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Contoh Penggunaan:**

```sql
-- Cek rate limit
SELECT request_count FROM rate_limits
WHERE identifier = '192.168.1.1'
  AND action = 'upload'
  AND window_end > NOW();

-- Insert/update
INSERT INTO rate_limits (identifier, action, request_count, window_start, window_end)
VALUES ('192.168.1.1', 'upload', 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))
ON DUPLICATE KEY UPDATE request_count = request_count + 1;
```

---

### 5.2 Tabel `audit_logs`

Menyimpan log aktivitas penting (login, register, reset password).

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- User
    user_id INT UNSIGNED NULL,
    
    -- Action
    action VARCHAR(50) NOT NULL,                        -- login, logout, register, reset_password
    description TEXT NULL,
    
    -- Client
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    -- Result
    status ENUM('success', 'failed') DEFAULT 'success',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    CONSTRAINT fk_audit_user 
        FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 6. Ringkasan Relasi

| Tabel | Relasi | On Delete | On Update |
|:---|:---|:---|:---|
| `images` → `users` | N:1 | SET NULL | CASCADE |
| `password_resets` → `users` | N:1 | CASCADE | CASCADE |
| `view_logs` → `images` | N:1 | CASCADE | CASCADE |
| `api_logs` → `users` | N:1 | SET NULL | CASCADE |
| `albums` → `users` | N:1 | CASCADE | CASCADE |
| `albums` → `images` (cover) | N:1 | SET NULL | CASCADE |
| `album_images` → `albums` | N:1 | CASCADE | CASCADE |
| `album_images` → `images` | N:1 | CASCADE | CASCADE |
| `audit_logs` → `users` | N:1 | SET NULL | CASCADE |

---

## 7. Migration SQL Lengkap

**File: `migrations/001_initial_schema.sql`**

```sql
-- ============================================================
-- PixiCDN Database Schema
-- Version: 1.0
-- Date: 2026-10-10
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ============================================================
-- 1. TABEL USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    recovery_code_hash VARCHAR(255) NULL,
    recovery_code_used_at TIMESTAMP NULL,
    api_key VARCHAR(64) NULL UNIQUE,
    api_key_created_at TIMESTAMP NULL,
    storage_quota BIGINT UNSIGNED DEFAULT 104857600,
    storage_used BIGINT UNSIGNED DEFAULT 0,
    display_name VARCHAR(100) NULL,
    avatar_url VARCHAR(500) NULL,
    bio TEXT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_api_key (api_key),
    INDEX idx_role (role),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABEL IMAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NULL,
    s3_key VARCHAR(500) NOT NULL,
    public_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    mime_type VARCHAR(50) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    size INT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    views INT UNSIGNED DEFAULT 0,
    downloads INT UNSIGNED DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    is_deleted BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_images_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_filename (filename),
    INDEX idx_user (user_id),
    INDEX idx_uploaded (uploaded_at),
    INDEX idx_expires (expires_at),
    INDEX idx_public (is_public),
    INDEX idx_deleted (is_deleted),
    INDEX idx_user_uploaded (user_id, uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABEL PASSWORD_RESETS
-- ============================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    method ENUM('recovery_code', 'email', 'admin') NOT NULL DEFAULT 'recovery_code',
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    INDEX idx_used (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABEL VIEW_LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS view_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    referrer VARCHAR(500) NULL,
    country VARCHAR(2) NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'bot', 'unknown') DEFAULT 'unknown',
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_views_image FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_image (image_id),
    INDEX idx_viewed (viewed_at),
    INDEX idx_ip (ip_address),
    INDEX idx_image_viewed (image_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABEL API_LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS api_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    endpoint VARCHAR(100) NOT NULL,
    method VARCHAR(10) NOT NULL,
    status_code SMALLINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    response_time_ms INT UNSIGNED NULL,
    request_size INT UNSIGNED NULL,
    response_size INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_api_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_status (status_code),
    INDEX idx_created (created_at),
    INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TABEL ALBUMS
-- ============================================================
CREATE TABLE IF NOT EXISTS albums (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT NULL,
    cover_image_id INT UNSIGNED NULL,
    is_public BOOLEAN DEFAULT TRUE,
    is_deleted BOOLEAN DEFAULT FALSE,
    image_count INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_albums_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_albums_cover FOREIGN KEY (cover_image_id) REFERENCES images(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uk_user_slug (user_id, slug),
    INDEX idx_user (user_id),
    INDEX idx_public (is_public),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. TABEL ALBUM_IMAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS album_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    album_id INT UNSIGNED NOT NULL,
    image_id INT UNSIGNED NOT NULL,
    position INT UNSIGNED DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_album FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ai_image FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uk_album_image (album_id, image_id),
    INDEX idx_album (album_id),
    INDEX idx_image (image_id),
    INDEX idx_position (album_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. TABEL RATE_LIMITS
-- ============================================================
CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    action VARCHAR(50) NOT NULL,
    request_count INT UNSIGNED DEFAULT 1,
    window_start TIMESTAMP NOT NULL,
    window_end TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_identifier_action (identifier, action),
    INDEX idx_window (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. TABEL AUDIT_LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    status ENUM('success', 'failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SELESAI
-- ============================================================
```

---

## 8. Sample Data

**File: `migrations/002_sample_data.sql`**

```sql
-- ============================================================
-- Sample Data untuk Testing
-- Password: "password123" (hash bcrypt cost 12)
-- ============================================================

-- Insert admin
INSERT INTO users (username, password_hash, recovery_code_hash, role, storage_quota) 
VALUES (
    'admin',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',  -- password123
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',  -- recovery: A3F8-K2M9
    'admin',
    1073741824  -- 1 GB
);

-- Insert user biasa
INSERT INTO users (username, password_hash, recovery_code_hash, role, storage_quota) 
VALUES (
    'raka_restu',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',  -- password123
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',  -- recovery: B4G9-L3N8
    'user',
    104857600  -- 100 MB
);

-- Insert sample images (anonymous)
INSERT INTO images (user_id, filename, original_name, s3_key, public_url, mime_type, extension, size, width, height) 
VALUES 
(NULL, '20261010_abc123.jpg', 'screenshot.png', 'uploads/2026/10/abc123.jpg', 
 'https://pixicdn.erdtreeproject.my.id/20261010_abc123.jpg', 
 'image/jpeg', 'jpg', 245760, 1920, 1080),

(NULL, '20261010_def456.png', 'diagram.png', 'uploads/2026/10/def456.png', 
 'https://pixicdn.erdtreeproject.my.id/20261010_def456.png', 
 'image/png', 'png', 102400, 800, 600);

-- Insert sample images (owned by raka_restu)
INSERT INTO images (user_id, filename, original_name, s3_key, public_url, mime_type, extension, size, width, height) 
VALUES 
(2, '20261010_ghi789.jpg', 'photo.jpg', 'uploads/2026/10/ghi789.jpg', 
 'https://pixicdn.erdtreeproject.my.id/20261010_ghi789.jpg', 
 'image/jpeg', 'jpg', 524288, 1280, 720);
```

---

## 9. Query Umum

### 9.1 Query untuk User

**Get user by username:**
```sql
SELECT * FROM users WHERE username = ? AND is_active = TRUE;
```

**Get user by API key:**
```sql
SELECT * FROM users WHERE api_key = ? AND is_active = TRUE;
```

**Update storage used:**
```sql
UPDATE users 
SET storage_used = storage_used + ? 
WHERE id = ?;
```

**Cek storage quota:**
```sql
SELECT 
    username,
    storage_used,
    storage_quota,
    ROUND((storage_used / storage_quota) * 100, 2) AS usage_percent
FROM users 
WHERE id = ?;
```

### 9.2 Query untuk Gambar

**Get images by user (dengan pagination):**
```sql
SELECT * FROM images 
WHERE user_id = ? 
  AND is_deleted = FALSE 
ORDER BY uploaded_at DESC 
LIMIT ? OFFSET ?;
```

**Get public images (gallery):**
```sql
SELECT * FROM images 
WHERE is_public = TRUE 
  AND is_deleted = FALSE 
  AND (expires_at IS NULL OR expires_at > NOW())
ORDER BY uploaded_at DESC 
LIMIT 20;
```

**Get image by filename:**
```sql
SELECT * FROM images 
WHERE filename = ? 
  AND is_deleted = FALSE;
```

**Increment view count:**
```sql
UPDATE images 
SET views = views + 1 
WHERE id = ?;
```

**Search images by original name:**
```sql
SELECT * FROM images 
WHERE original_name LIKE ? 
  AND is_deleted = FALSE
ORDER BY uploaded_at DESC;
```

### 9.3 Query untuk Analytics

**Total images per user:**
```sql
SELECT 
    u.username,
    COUNT(i.id) AS total_images,
    SUM(i.size) AS total_size,
    SUM(i.views) AS total_views
FROM users u
LEFT JOIN images i ON u.id = i.user_id AND i.is_deleted = FALSE
GROUP BY u.id;
```

**Top 10 most viewed images:**
```sql
SELECT filename, views, public_url 
FROM images 
WHERE is_deleted = FALSE 
ORDER BY views DESC 
LIMIT 10;
```

**Uploads per day (7 hari terakhir):**
```sql
SELECT 
    DATE(uploaded_at) AS date,
    COUNT(*) AS total
FROM images 
WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(uploaded_at)
ORDER BY date DESC;
```

**Storage usage per user:**
```sql
SELECT 
    username,
    ROUND(storage_used / 1024 / 1024, 2) AS used_mb,
    ROUND(storage_quota / 1024 / 1024, 2) AS quota_mb,
    ROUND((storage_used / storage_quota) * 100, 2) AS percent
FROM users 
ORDER BY storage_used DESC;
```

### 9.4 Query untuk Cleanup

**Gambar yang expired:**
```sql
SELECT * FROM images 
WHERE expires_at IS NOT NULL 
  AND expires_at < NOW() 
  AND is_deleted = FALSE;
```

**Gambar soft-deleted lebih dari 30 hari:**
```sql
SELECT * FROM images 
WHERE is_deleted = TRUE 
  AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

**Token reset yang expired:**
```sql
DELETE FROM password_resets 
WHERE expires_at < NOW() 
   OR used_at IS NOT NULL;
```

**View logs lebih dari 90 hari:**
```sql
DELETE FROM view_logs 
WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 📊 Ringkasan Tabel

| # | Tabel | Fungsi | Estimasi Row |
|:---|:---|:---|:---|
| 1 | `users` | Data user | Kecil (< 1000) |
| 2 | `images` | Metadata gambar | Sedang (1000-10000) |
| 3 | `password_resets` | Token reset | Kecil (< 100) |
| 4 | `view_logs` | Analytics view | **Besar** (juta) |
| 5 | `api_logs` | API monitoring | Sedang |
| 6 | `albums` | Album | Kecil |
| 7 | `album_images` | Pivot album-gambar | Sedang |
| 8 | `rate_limits` | Rate limiting | Kecil |
| 9 | `audit_logs` | Audit trail | Sedang |

---

## 🎯 Catatan Desain

### Mengapa Menggunakan:

| Keputusan | Alasan |
|:---|:---|
| **INT UNSIGNED** | Lebih efisien dari INT biasa, tidak perlu nilai negatif |
| **BIGINT untuk views/logs** | Bisa menampung miliaran row |
| **ENUM** | Lebih efisien dari VARCHAR untuk nilai terbatas |
| **Soft delete** | Bisa restore jika user tidak sengaja hapus |
| **Timestamps** | Standard untuk audit trail |
| **Foreign keys** | Menjaga integritas data |
| **Indexes** | Mempercepat query yang sering dipakai |
| **utf8mb4** | Mendukung emoji dan karakter unicode |

### Yang Perlu Diperhatikan:

| Item | Catatan |
|:---|:---|
| **view_logs** | Bisa sangat besar. Pertimbangkan partitioning atau agregasi |
| **Soft delete** | Query harus selalu `WHERE is_deleted = FALSE` |
| **Rate limits** | Perlu cron job untuk cleanup row yang expired |
| **Audit logs** | Perlu retensi (misalnya 1 tahun) |
| **Storage quota** | Update `storage_used` setiap upload/delete |

---

## 📁 Struktur File Migration

```
migrations/
├── 001_initial_schema.sql       # Semua tabel
├── 002_sample_data.sql          # Data testing
├── 003_add_indexes.sql          # Index tambahan (opsional)
└── 004_add_views_logs.sql       # View logs (opsional)
```

**Cara menjalankan:**

```bash
# Via terminal
mariadb -h 192.168.1.50 -u pixicdn_user -p pixicdn_db < migrations/001_initial_schema.sql

# Via phpMyAdmin
# Import file 001_initial_schema.sql
```

---

## ✅ Kesimpulan

Skema database PixiCDN mencakup:

- ✅ **9 tabel** — core (3), analytics (2), album (2), keamanan (2)
- ✅ **Foreign keys** dengan `ON DELETE CASCADE` / `SET NULL`
- ✅ **Indexes** untuk query performa
- ✅ **Soft delete** untuk gambar
- ✅ **Rate limiting** untuk mencegah spam
- ✅ **Audit logs** untuk keamanan
- ✅ **Sample data** untuk testing
- ✅ **Query umum** untuk development

