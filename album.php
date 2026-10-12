<?php
/**
 * ============================================================
 * File: album.php
 * Deskripsi: Viewer album publik via slug (tanpa login jika publik)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * URL: /album.php?u=<username>&s=<slug>
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php'; // dev-fill saja, guarded defined()
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/album.php';

$username = trim($_GET['u'] ?? '');
$slug = trim($_GET['s'] ?? '');
if ($username === '' || $slug === '') {
    http_response_code(404);
    exit('Album tidak ditemukan');
}

start_secure_session();
$me = null;
try {
    $me = current_user();
} catch (PDOException $e) {
    error_log('Album publik user gagal: ' . $e->getMessage());
}

try {
    $stmt = getDB()->prepare('SELECT a.* FROM albums a JOIN users u ON u.id = a.user_id WHERE u.username = ? AND a.slug = ? AND a.is_deleted = FALSE');
    $stmt->execute([$username, $slug]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Album publik gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada sistem.');
}
if (!$row) {
    http_response_code(404);
    exit('Album tidak ditemukan');
}

// Reuse tested ownership path: allow_public=true, lalu hormati is_public eksplisit
$is_owner = $me !== null && (int) $row['user_id'] === (int) $me['id'];
if (!$is_owner && !(bool) $row['is_public']) {
    // Samakan 404 agar album privat tidak enumerable
    http_response_code(404);
    exit('Album tidak ditemukan');
}
$album = get_album((int) $row['id'], $me !== null ? (int) $me['id'] : 0, allow_public: true) ?? $row;

try {
    $stmt = getDB()->prepare('SELECT i.filename, i.public_url, i.thumbnail_url FROM images i JOIN album_images ai ON ai.image_id = i.id WHERE ai.album_id = ? AND i.is_deleted = FALSE AND (i.expires_at IS NULL OR i.expires_at > NOW()) ORDER BY ai.position, ai.added_at DESC');
    $stmt->execute([(int) $album['id']]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Album publik items gagal: ' . $e->getMessage());
    $items = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($album['name']) ?> — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Work Sans',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<a href="/gallery.php" class="hover:text-[#003087] font-medium">Gallery</a>
</nav>
</header>
<main class="max-w-[1120px] mx-auto px-6 py-10">
<h1 class="font-bold" style="font-size:32px"><?= sanitize($album['name']) ?></h1>
<p class="text-[#687173] text-sm mb-6"><span class="tnum"><?= (int) $album['image_count'] ?> gambar</span> · oleh <?= sanitize($username) ?></p>
<?php if (!$items): ?><p class="text-[#687173]">Album ini kosong.</p><?php else: ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-6">
<?php foreach ($items as $img): ?>
<a href="/image.php?f=<?= sanitize($img['filename']) ?>" class="bg-white border border-[#CBD2D6] rounded-xl overflow-hidden block"><img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>" alt="<?= sanitize($img['filename']) ?>" loading="lazy" class="w-full h-40 object-cover"></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<p class="mt-6"><a href="/gallery.php" class="text-[#003087] font-medium underline">Gallery</a></p>
</main>
</body>
</html>
