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
<div id="sb-backdrop" class="fixed inset-0 bg-black/40 hidden md:hidden z-30"></div>
<aside id="sb" class="fixed top-0 left-0 h-full w-60 bg-white border-r border-[#CBD2D6] z-40 -translate-x-full md:translate-x-0 transition-transform flex flex-col" aria-label="Menu dashboard">
<div class="h-16 flex items-center px-6 border-b border-[#CBD2D6]">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</div>
<nav class="p-4 space-y-1">
<p class="px-3 pt-2 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Menu</p>
<a class="navlink active" href="/dashboard/" aria-current="page"><i data-lucide="layout-dashboard"></i>Dashboard</a>
<a class="navlink" href="/upload.php"><i data-lucide="upload"></i>Upload</a>
<a class="navlink" href="/gallery.php"><i data-lucide="image"></i>Gallery</a>
<a class="navlink" href="/dashboard/albums.php"><i data-lucide="folder"></i>Album</a>
<p class="px-3 pt-4 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Akun</p>
<a class="navlink" href="/auth/profile.php"><i data-lucide="user"></i>Profil</a>
<a class="navlink" href="/dashboard/api-key.php"><i data-lucide="key"></i>API key</a>
<a class="navlink" href="/auth/logout.php"><i data-lucide="log-out"></i>Keluar</a>
</nav>
</aside>
<div class="md:ml-60 min-h-screen flex flex-col">
<header class="md:hidden bg-white border-b border-[#CBD2D6] h-16 flex items-center gap-3 px-4 sticky top-0 z-20">
<button id="sb-open" class="p-2 rounded-lg hover:bg-[#F5F7FA]" aria-label="Buka menu"><i data-lucide="menu"></i></button>
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</header>
<main class="max-w-[1120px] w-full mx-auto px-6 py-10">
<h1 class="font-bold mb-6" style="font-size:32px">Halo, <?= sanitize($user['username']) ?></h1>
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
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
const sb = document.getElementById('sb'), bd = document.getElementById('sb-backdrop');
function setSb(open) { sb.classList.toggle('-translate-x-full', !open); bd.classList.toggle('hidden', !open); }
document.getElementById('sb-open').addEventListener('click', () => setSb(true));
bd.addEventListener('click', () => setSb(false));
document.querySelectorAll('#sb a').forEach(a => a.addEventListener('click', () => setSb(false)));
new Chart(document.getElementById('ch'), { type: 'bar',
  data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ data: <?= json_encode($chart_data) ?>, backgroundColor: 'rgba(0,48,135,0.15)', borderColor: '#003087', borderWidth: 1 }] },
  options: { plugins: { legend: { display: false } } } });
new Chart(document.getElementById('chv'), { type: 'line',
  data: { labels: <?= json_encode($views30_labels) ?>, datasets: [{ data: <?= json_encode($views30_data) ?>, borderColor: '#009CDE', tension: 0.3 }] },
  options: { plugins: { legend: { display: false } } } });
</script>
</body>
</html>
