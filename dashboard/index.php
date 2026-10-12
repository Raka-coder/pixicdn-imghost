<?php
/**
 * ============================================================
 * File: dashboard/index.php
 * Deskripsi: Dashboard user (statistik, chart, top gambar) — Bootstrap 5.3
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/config.example.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

require_login();
$user = current_user();
$active = 'dashboard';
$crumbs = [['label' => 'Dashboard']];
$back_url = null;
$load_chartjs = true;

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
<?php
$page_title = 'Dashboard — PixiCDN';
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar Desktop -->
        <aside class="col-md-3 col-lg-2 d-none d-md-block bg-white border-end border-2 border-primary min-vh-100 position-sticky top-0 sidebar-desktop">
            <?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-5">
            <!-- Breadcrumb + Back Button -->
            <nav aria-label="Breadcrumb" class="mb-4">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <?php if ($back_url !== null): ?>
                        <a href="<?= sanitize($back_url) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                    <?php endif; ?>
                    <ol class="breadcrumb mb-0 flex-wrap">
                        <?php foreach ($crumbs as $i => $c): ?>
                            <?php if ($i > 0): ?>
                                <li class="breadcrumb-item"><i class="bi bi-chevron-right text-secondary"></i></li>
                            <?php endif; ?>
                            <?php if (isset($c['url'])): ?>
                                <li class="breadcrumb-item"><a href="<?= sanitize($c['url']) ?>" class="text-decoration-none text-secondary"><?= sanitize($c['label']) ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active fw-semibold text-body-emphasis" aria-current="page"><?= sanitize($c['label']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </nav>

            <h1 class="page-title mb-4">Halo, <?= sanitize($user['username']) ?></h1>

            <!-- Quick Actions -->
            <div class="row g-3 mb-4" aria-label="Aksi cepat">
                <div class="col-6 col-md-3">
                    <a href="/upload.php" class="card card-trust h-100 d-flex align-items-center gap-3 text-decoration-none">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 icon-box">
                            <i class="bi bi-cloud-upload fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Upload</div>
                            <div class="text-secondary small">Gambar baru</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/gallery.php" class="card card-trust h-100 d-flex align-items-center gap-3 text-decoration-none">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 icon-box">
                            <i class="bi bi-images fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Gallery</div>
                            <div class="text-secondary small">Koleksi saya</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/dashboard/albums.php" class="card card-trust h-100 d-flex align-items-center gap-3 text-decoration-none">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 icon-box">
                            <i class="bi bi-folder-plus fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">Album</div>
                            <div class="text-secondary small">Buat & atur</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/dashboard/api-key.php" class="card card-trust h-100 d-flex align-items-center gap-3 text-decoration-none">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 icon-box">
                            <i class="bi bi-key fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">API Key</div>
                            <div class="text-secondary small">Integrasi</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-trust p-4 text-center h-100">
                        <p class="text-secondary small mb-1">Total gambar</p>
                        <p class="display-4 fw-bold tnum text-primary"><?= (int) $stats['total'] ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-trust p-4 text-center h-100">
                        <p class="text-secondary small mb-1">Total views</p>
                        <p class="display-4 fw-bold tnum text-info"><?= (int) $stats['views'] ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-trust p-4 text-center h-100">
                        <p class="text-secondary small mb-1">Storage</p>
                        <p class="display-4 fw-bold tnum text-warning"><?= round(((int) $user['storage_used']) / 1048576, 2) ?> / <?= round(((int) $user['storage_quota']) / 1048576) ?> MB</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card card-trust h-100">
                        <div class="card-body">
                            <h2 class="fw-semibold mb-3">Upload 7 hari terakhir</h2>
                            <canvas id="ch" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-trust h-100">
                        <div class="card-body">
                            <h2 class="fw-semibold mb-3">Views 30 hari terakhir</h2>
                            <canvas id="chv" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 10 -->
            <div class="card card-trust">
                <div class="card-body">
                    <h2 class="fw-semibold mb-3">Top 10 terpopuler</h2>
                    <?php if ($top): ?>
                        <div class="row g-3">
                            <?php foreach ($top as $t): ?>
                                <div class="col-6 col-md-4 col-xl-2">
                                    <a href="/image.php?f=<?= sanitize($t['filename']) ?>" class="card card-trust h-100 text-decoration-none d-block">
                                        <div class="ratio ratio-4x3 overflow-hidden">
                                            <img src="<?= sanitize($t['thumbnail_url'] ?: $t['public_url']) ?>" alt="<?= sanitize($t['filename']) ?>" loading="lazy" class="w-100 h-100 object-fit-cover">
                                        </div>
                                        <div class="p-2">
                                            <p class="text-secondary small tnum mb-0"><?= (int) $t['views'] ?> views</p>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-secondary text-center py-4 mb-0">Belum ada gambar.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart.js initialization
    new Chart(document.getElementById('ch'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: 'rgba(0, 48, 135, 0.15)',
                borderColor: '#003087',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
    new Chart(document.getElementById('chv'), {
        type: 'line',
        data: {
            labels: <?= json_encode($views30_labels) ?>,
            datasets: [{
                data: <?= json_encode($views30_data) ?>,
                borderColor: '#009CDE',
                backgroundColor: 'rgba(0, 156, 222, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>