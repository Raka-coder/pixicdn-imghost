<?php
/**
 * ============================================================
 * File: dashboard/index.php
 * Deskripsi: Dashboard user (statistik, chart, top gambar)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/config.example.php'; // dev-fill saja, guarded defined()
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();
$user = current_user();
$active = 'dashboard';
$crumbs = [['label' => 'Dashboard']];
$back_url = null;
$stats = ['total' => 0, 'views' => 0];
$chart_labels = [];
$chart_data = [];
$views30_labels = [];
$views30_data = [];
$top = [];
try {
    $stmt = getDB()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(views),0) AS views FROM images WHERE user_id = ? AND is_deleted = FALSE');
    $stmt->execute([(int) $user['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $stats = ['total' => (int) $row['total'], 'views' => (int) $row['views']];
    }
    $stmt = getDB()->prepare('SELECT DATE(uploaded_at) AS d, COUNT(*) AS c FROM images WHERE user_id = ? AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(uploaded_at) ORDER BY d');
    $stmt->execute([(int) $user['id']]);
    $map = [];
    foreach ($stmt->fetchAll() as $r) {
        $map[$r['d']] = (int) $r['c'];
    }
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = $d;
        $chart_data[] = $map[$d] ?? 0;
    }
    $stmt = getDB()->prepare('SELECT DATE(viewed_at) AS d, COUNT(*) AS c FROM view_logs v JOIN images i ON i.id = v.image_id WHERE i.user_id = ? AND v.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(viewed_at) ORDER BY d');
    $stmt->execute([(int) $user['id']]);
    $vmap = [];
    foreach ($stmt->fetchAll() as $r) {
        $vmap[$r['d']] = (int) $r['c'];
    }
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $views30_labels[] = substr($d, 5);
        $views30_data[] = $vmap[$d] ?? 0;
    }
    $stmt = getDB()->prepare('SELECT filename, public_url, thumbnail_url, views FROM images WHERE user_id = ? AND is_deleted = FALSE ORDER BY views DESC LIMIT 10');
    $stmt->execute([(int) $user['id']]);
    $top = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Dashboard stats gagal: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen">
<?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="max-w-[1120px] w-full mx-auto px-6 py-10">
<?php require dirname(__DIR__) . '/includes/breadcrumbs.php'; ?>
<h1 class="page-title mb-6">Halo, <?= sanitize($user['username']) ?></h1>
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6" aria-label="Aksi cepat">
<a href="/upload.php" class="card flex items-center gap-3">
<span class="p-2 rounded-lg bg-[#EAF0FA] text-[#003087]"><i data-lucide="upload" class="w-5 h-5"></i></span>
<span><span class="block font-semibold">Upload</span><span class="block text-xs text-[#687173]">Gambar baru</span></span>
</a>
<a href="/gallery.php" class="card flex items-center gap-3">
<span class="p-2 rounded-lg bg-[#EAF0FA] text-[#003087]"><i data-lucide="image" class="w-5 h-5"></i></span>
<span><span class="block font-semibold">Gallery</span><span class="block text-xs text-[#687173]">Koleksi saya</span></span>
</a>
<a href="/dashboard/albums.php" class="card flex items-center gap-3">
<span class="p-2 rounded-lg bg-[#EAF0FA] text-[#003087]"><i data-lucide="folder-plus" class="w-5 h-5"></i></span>
<span><span class="block font-semibold">Album</span><span class="block text-xs text-[#687173]">Buat &amp; atur</span></span>
</a>
<a href="/dashboard/api-key.php" class="card flex items-center gap-3">
<span class="p-2 rounded-lg bg-[#EAF0FA] text-[#003087]"><i data-lucide="key" class="w-5 h-5"></i></span>
<span><span class="block font-semibold">API key</span><span class="block text-xs text-[#687173]">Integrasi</span></span>
</a>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<div class="card"><p class="text-[#687173] text-sm">Total gambar</p><p class="tnum font-bold" style="font-size:32px"><?= (int) $stats['total'] ?></p></div>
<div class="card"><p class="text-[#687173] text-sm">Total views</p><p class="tnum font-bold" style="font-size:32px"><?= (int) $stats['views'] ?></p></div>
<div class="card"><p class="text-[#687173] text-sm">Storage</p><p class="tnum font-bold" style="font-size:32px"><?= round(((int) $user['storage_used']) / 1048576, 2) ?> / <?= round(((int) $user['storage_quota']) / 1048576) ?> MB</p></div>
</div>
<div class="card mt-6">
<h2 class="font-semibold mb-2 text-lg">Upload 7 hari terakhir</h2>
<canvas id="ch" height="120"></canvas>
</div>
<div class="card mt-6">
<h2 class="font-semibold mb-2 text-lg">Views 30 hari terakhir</h2>
<canvas id="chv" height="120"></canvas>
</div>
<div class="card mt-6">
<h2 class="font-semibold mb-2 text-lg">Top 10 terpopuler</h2>
<div class="grid grid-cols-2 md:grid-cols-5 gap-6">
<?php foreach ($top as $t): ?>
<a href="/image.php?f=<?= sanitize($t['filename']) ?>" class="block">
<img src="<?= sanitize($t['thumbnail_url'] ?: $t['public_url']) ?>" alt="<?= sanitize($t['filename']) ?>" loading="lazy" class="w-full h-24 object-cover rounded-lg border border-[#CBD2D6]">
<p class="text-xs text-[#687173] mt-1 tnum"><?= (int) $t['views'] ?> views</p>
</a>
<?php endforeach; ?>
</div>
<?php if (!$top): ?><p class="text-[#687173] text-sm">Belum ada gambar.</p><?php endif; ?>
</div>
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
new Chart(document.getElementById('ch'), { type: 'bar',
  data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ data: <?= json_encode($chart_data) ?>, backgroundColor: 'rgba(0,48,135,0.15)', borderColor: '#003087', borderWidth: 1 }] },
  options: { plugins: { legend: { display: false } } } });
new Chart(document.getElementById('chv'), { type: 'line',
  data: { labels: <?= json_encode($views30_labels) ?>, datasets: [{ data: <?= json_encode($views30_data) ?>, borderColor: '#009CDE', tension: 0.3 }] },
  options: { plugins: { legend: { display: false } } } });
</script>
</body>
</html>
