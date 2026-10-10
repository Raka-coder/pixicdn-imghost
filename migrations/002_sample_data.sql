-- ============================================================
-- PixiCDN Seed Testing (Tahap 6)
-- Password semua user: "password123" — JANGAN dipakai di production
-- Idempotent: aman dijalankan ulang (INSERT IGNORE)
-- Jalankan SETELAH 001_initial_schema.sql
-- ============================================================

-- Admin (quota 1 GB)
INSERT IGNORE INTO users (username, password_hash, recovery_code_hash, role, storage_quota)
VALUES (
    'admin',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',
    'admin',
    1073741824
);

-- User biasa (quota 100 MB)
INSERT IGNORE INTO users (username, password_hash, recovery_code_hash, role, storage_quota)
VALUES (
    'raka_restu',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYqVr/if1Hy',
    'user',
    104857600
);

-- Gambar anonymous contoh (abaikan jika filename sudah ada)
INSERT INTO images (user_id, filename, original_name, s3_key, public_url, mime_type, extension, size, width, height)
SELECT NULL, '20261010_abc123.jpg', 'screenshot.png', 'uploads/2026/10/20261010_abc123.jpg',
    'https://pixicdn.erdtreeproject.my.id/20261010_abc123.jpg',
    'image/jpeg', 'jpg', 245760, 1920, 1080
WHERE NOT EXISTS (SELECT 1 FROM images WHERE filename = '20261010_abc123.jpg');

INSERT INTO images (user_id, filename, original_name, s3_key, public_url, mime_type, extension, size, width, height)
SELECT NULL, '20261010_def456.png', 'diagram.png', 'uploads/2026/10/20261010_def456.png',
    'https://pixicdn.erdtreeproject.my.id/20261010_def456.png',
    'image/png', 'png', 102400, 800, 600
WHERE NOT EXISTS (SELECT 1 FROM images WHERE filename = '20261010_def456.png');

-- Gambar milik raka_restu
INSERT INTO images (user_id, filename, original_name, s3_key, public_url, mime_type, extension, size, width, height)
SELECT id, '20261010_ghi789.jpg', 'photo.jpg', 'uploads/2026/10/20261010_ghi789.jpg',
    'https://pixicdn.erdtreeproject.my.id/20261010_ghi789.jpg',
    'image/jpeg', 'jpg', 524288, 1280, 720
FROM users WHERE username = 'raka_restu'
    AND NOT EXISTS (SELECT 1 FROM images WHERE filename = '20261010_ghi789.jpg');

-- Album contoh milik raka_restu
INSERT INTO albums (user_id, name, slug, description)
SELECT id, 'Contoh Album', 'contoh-album', 'Album contoh untuk testing'
FROM users WHERE username = 'raka_restu'
    AND NOT EXISTS (SELECT 1 FROM albums WHERE slug = 'contoh-album' AND user_id = (SELECT id FROM users WHERE username = 'raka_restu'));
