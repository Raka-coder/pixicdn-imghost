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
require_once dirname(__DIR__) . '/config.example.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();
$user = current_user();
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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Inter',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<div class="flex gap-2 text-sm font-medium">
<a href="/upload.php" class="h-12 px-4 leading-[48px] bg-[#003087] text-white font-semibold rounded-lg">Upload</a>
<a href="/gallery.php" class="hover:text-[#003087] leading-[48px]">Gallery</a>
<a href="/dashboard/albums.php" class="hover:text-[#003087] leading-[48px]">Album</a>
<a href="/auth/profile.php" class="hover:text-[#003087] leading-[48px]">Profil</a>
<a href="/dashboard/api-key.php" class="hover:text-[#003087] leading-[48px]">API key</a>
<a href="/auth/logout.php" class="hover:text-[#003087] leading-[48px]">Keluar</a>
</div>
</nav>
</header>
<main class="max-w-[1120px] mx-auto px-6 py-10">
<h1 class="font-bold mb-6" style="font-size:32px">Halo, <?= sanitize($user['username']) ?></h1>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6"><p class="text-[#687173] text-sm">Total gambar</p><p class="tnum font-bold" style="font-size:32px;font-feature-settings:'tnum'"><?= (int) $stats['total'] ?></p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6"><p class="text-[#687173] text-sm">Total views</p><p class="tnum font-bold" style="font-size:32px;font-feature-settings:'tnum'"><?= (int) $stats['views'] ?></p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6"><p class="text-[#687173] text-sm">Storage</p><p class="tnum font-bold" style="font-size:32px;font-feature-settings:'tnum'"><?= round(((int) $user['storage_used']) / 1048576, 2) ?> / <?= round(((int) $user['storage_quota']) / 1048576) ?> MB</p></div>
</div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 mt-6">
<h2 class="font-semibold mb-2 text-lg">Upload 7 hari terakhir</h2>
<canvas id="ch" height="120"></canvas>
</div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 mt-6">
<h2 class="font-semibold mb-2 text-lg">Views 30 hari terakhir</h2>
<canvas id="chv" height="120"></canvas>
</div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 mt-6">
<h2 class="font-semibold mb-2 text-lg">Top 10 terpopuler</h2>
<div class="grid grid-cols-2 md:grid-cols-5 gap-6">
<?php foreach ($top as $t): ?>
<a href="/image.php?f=<?= sanitize($t['filename']) ?>" class="block">
<img src="<?= sanitize($t['thumbnail_url'] ?: $t['public_url']) ?>" alt="<?= sanitize($t['filename']) ?>" loading="lazy" class="w-full h-24 object-cover rounded-lg border border-[#CBD2D6]">
<p class="text-xs text-[#687173] mt-1 tnum" style="font-feature-settings:'tnum'"><?= (int) $t['views'] ?> views</p>
</a>
<?php endforeach; ?>
</div>
<?php if (!$top): ?><p class="text-[#687173] text-sm">Belum ada gambar.</p><?php endif; ?>
</div>
</main>
<script>
new Chart(document.getElementById('ch'), { type: 'bar',
  data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ data: <?= json_encode($chart_data) ?>, backgroundColor: 'rgba(0,48,135,0.15)', borderColor: '#003087', borderWidth: 1 }] },
  options: { plugins: { legend: { display: false } } } });
new Chart(document.getElementById('chv'), { type: 'line',
  data: { labels: <?= json_encode($views30_labels) ?>, datasets: [{ data: <?= json_encode($views30_data) ?>, borderColor: '#009CDE', tension: 0.3 }] },
  options: { plugins: { legend: { display: false } } } });
</script>
</body>
</html>
