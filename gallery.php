<?php
/**
 * ============================================================
 * File: gallery.php
 * Deskripsi: Gallery pribadi user login (filter, sort, pagination 20)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
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

start_secure_session();
require_login(); // Wajib akun — koleksi pribadi user
$user = current_user();
$uid = (int) $user['id'];
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 20;
$off = ($page - 1) * $per;

$where = 'user_id = ' . $uid . ' AND is_deleted = FALSE AND (expires_at IS NULL OR expires_at > NOW())';
$params = [];
if ($filter === 'today') {
    $where .= ' AND DATE(uploaded_at) = CURDATE()';
} elseif ($filter === 'week') {
    $where .= ' AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($filter === 'month') {
    $where .= ' AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}
$order = match ($sort) {
    'oldest' => 'uploaded_at ASC',
    'views' => 'views DESC',
    default => 'uploaded_at DESC',
};

try {
    $total = (int) getDB()->query("SELECT COUNT(*) FROM images WHERE $where")->fetchColumn();
    $stmt = getDB()->prepare("SELECT id, filename, public_url, thumbnail_url, views, user_id, uploaded_at FROM images WHERE $where ORDER BY $order LIMIT $per OFFSET $off");
    $stmt->execute($params);
    $images = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Gallery gagal: ' . $e->getMessage());
    $images = [];
    $total = 0;
}
$pages = max(1, (int) ceil($total / $per));
$active = 'gallery';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'Gallery']];
$back_url = '/dashboard/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gallery — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen">
<?php require __DIR__ . '/includes/sidebar.php'; ?>
<main class="max-w-[1120px] w-full mx-auto px-6 py-10">
<?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
<h1 class="page-title mb-6">Gallery</h1>
<form method="get" class="flex flex-wrap gap-2 mb-6">
<select name="filter" class="input input-auto" aria-label="Filter waktu">
<?php foreach (['all' => 'Semua', 'today' => 'Hari ini', 'week' => 'Minggu ini', 'month' => 'Bulan ini'] as $k => $l): ?>
<option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $l ?></option>
<?php endforeach; ?>
</select>
<select name="sort" class="input input-auto" aria-label="Urutkan">
<?php foreach (['newest' => 'Terbaru', 'oldest' => 'Terlama', 'views' => 'Terpopuler'] as $k => $l): ?>
<option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $l ?></option>
<?php endforeach; ?>
</select>
<button class="btn btn-secondary">Terapkan</button>
</form>
<?php if (!$images): ?>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 text-center py-16">
<h3 class="text-xl font-semibold mb-2">Belum ada gambar</h3>
<p class="text-[#687173] mb-6">Upload gambar pertama Anda untuk memulai</p>
<a href="/upload.php" class="btn btn-primary">Upload Sekarang</a>
</div>
<?php else: ?>
<div class="grid grid-cols-2 md:grid-cols-4 gap-6">
<?php foreach ($images as $img): ?>
<div class="bg-white border border-[#CBD2D6] rounded-xl overflow-hidden">
<a href="/image.php?f=<?= sanitize($img['filename']) ?>"><img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>" alt="<?= sanitize($img['filename']) ?>" loading="lazy" class="w-full h-40 object-cover"></a>
<div class="p-4 flex items-center justify-between text-sm text-[#687173]">
<span class="tnum"><?= (int) $img['views'] ?> views</span>
<form method="post" action="/delete.php" onsubmit="return confirm('Hapus gambar ini?')">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
<button class="text-[#D20000] font-medium">Hapus</button>
</form>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="flex flex-wrap gap-2 mt-6">
<?php for ($p = 1; $p <= $pages; $p++): ?>
<a href="?filter=<?= sanitize($filter) ?>&sort=<?= sanitize($sort) ?>&page=<?= $p ?>" class="btn-page<?= $p === $page ? ' active' : '' ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
<?php endfor; ?>
</div>
<?php endif; ?>
</main>
</div>
<footer class="md:ml-60 border-t border-[#CBD2D6] bg-white">
<div class="max-w-[1120px] mx-auto px-6 py-4 text-sm text-[#687173]">
<a href="/" class="hover:text-[#003087] font-medium">Halaman Utama</a>
</div>
</footer>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
</script>
</body>
</html>
