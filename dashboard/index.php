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
require_once dirname(__DIR__) . '/includes/dashboard.php';

require_login();
$user = current_user();
$active = 'dashboard';
$crumbs = [['label' => 'Dashboard']];
$back_url = null;
$load_chartjs = true;

// Satu sumber metrik dari database (bukan dummy). Gagal DB → flag error,
// halaman tetap tampil dengan angka 0 + peringatan, bukan angka palsu.
$metrics = ['total' => 0, 'views' => 0, 'storage_used' => 0, 'storage_quota' => 0, 'uploads7' => ['labels' => [], 'data' => []], 'views30' => ['labels' => [], 'data' => []], 'top' => []];
$stats_error = false;
try {
    $metrics = get_dashboard_stats((int) $user['id']);
} catch (PDOException $e) {
    error_log('Dashboard stats gagal: ' . $e->getMessage());
    $stats_error = true;
}
$chart_labels = $metrics['uploads7']['labels'];
$chart_data = $metrics['uploads7']['data'];
$views30_labels = $metrics['views30']['labels'];
$views30_data = $metrics['views30']['data'];
$top = $metrics['top'];
$stats = ['total' => $metrics['total'], 'views' => $metrics['views']];
?>
<?php
$page_title = 'Dashboard — PixiCDN';
$is_dashboard = true;
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar Desktop -->
        <aside class="col-md-3 col-lg-2 d-none d-md-block bg-white border-end border-2 border-primary min-vh-100 position-sticky top-0 sidebar-desktop">
            <?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-4 py-5">
            <!-- Breadcrumb + Back Button -->
            <?php render_breadcrumbs($crumbs, $back_url); ?>

            <h1 class="page-title mb-2">Halo, <?= sanitize($user['username']) ?></h1>
            <div class="d-flex align-items-center gap-2 mb-4">
                <button type="button" id="refreshStats" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-clockwise me-1"></i>Perbarui</button>
                <span class="text-secondary small" id="statsUpdated">Dimuat <?= date('H:i:s') ?></span>
            </div>
            <?php if ($stats_error): ?>
            <div class="alert alert-warning alert-permanent" role="alert">Data statistik gagal dimuat dari database. Angka di bawah mungkin tidak akurat — coba tekan Perbarui.</div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row g-3 mb-4" aria-label="Aksi cepat">
                <div class="col-6 col-md-3">
                    <a href="/upload.php" class="card card-trust h-100 d-flex align-items-center gap-2 text-decoration-none p-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-cloud-upload fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Upload</div>
                            <div class="text-secondary small">Gambar baru</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/gallery.php" class="card card-trust h-100 d-flex align-items-center gap-2 text-decoration-none p-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-images fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Gallery</div>
                            <div class="text-secondary small">Koleksi saya</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/dashboard/albums.php" class="card card-trust h-100 d-flex align-items-center gap-2 text-decoration-none p-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-folder-plus fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">Album</div>
                            <div class="text-secondary small">Buat & atur</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="/dashboard/api-key.php" class="card card-trust h-100 d-flex align-items-center gap-2 text-decoration-none p-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <i class="bi bi-key fs-5"></i>
                        </div>
                        <div>
                            <div class="fw-semibold small">API Key</div>
                            <div class="text-secondary small">Integrasi</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card card-trust p-4 text-center h-100">
                        <p class="text-secondary small mb-1"><i class="bi bi-images me-1"></i>Total gambar</p>
                        <p class="display-4 fw-bold tnum text-primary" id="statTotal"><?= (int) $stats['total'] ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-trust p-4 text-center h-100">
                        <p class="text-secondary small mb-1"><i class="bi bi-eye me-1"></i>Total views</p>
                        <p class="display-4 fw-bold tnum text-info" id="statViews"><?= (int) $stats['views'] ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-trust p-4 text-center h-100">
                        <p class="text-secondary small mb-1"><i class="bi bi-hdd me-1"></i>Storage</p>
                        <p class="display-4 fw-bold tnum text-warning" id="statStorage"><?= round(((int) $user['storage_used']) / 1048576, 2) ?> / <?= round(((int) $user['storage_quota']) / 1048576) ?> MB</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card card-trust h-100">
                        <div class="card-body">
                            <h2 class="fw-semibold mb-3">Upload 7 hari terakhir</h2>
                            <div class="chart-box"><canvas id="ch"></canvas></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-trust h-100">
                        <div class="card-body">
                            <h2 class="fw-semibold mb-3">Views 30 hari terakhir</h2>
                            <div class="chart-box"><canvas id="chv"></canvas></div>
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
    if (typeof Chart === 'undefined') {
        document.getElementById('statsUpdated').textContent = 'Grafik gagal dimuat (CDN Chart.js tidak dapat diakses).';
        return;
    }
    const chartOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    };
    // Chart.js initialization (data awal dari database saat render)
    const chChart = new Chart(document.getElementById('ch'), {
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
        options: chartOpts
    });
    const chvChart = new Chart(document.getElementById('chv'), {
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
        options: chartOpts
    });

    // Refresh metrik realtime dari database (tanpa reload halaman)
    async function refreshStats() {
        try {
            const res = await fetch('/dashboard/stats.php', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const j = await res.json();
            if (!j.success) return;
            const d = j.data;
            document.getElementById('statTotal').textContent = Number(d.total).toLocaleString('en-US');
            document.getElementById('statViews').textContent = Number(d.views).toLocaleString('en-US');
            document.getElementById('statStorage').textContent = (d.storage_used / 1048576).toFixed(2) + ' / ' + Math.round(d.storage_quota / 1048576) + ' MB';
            chChart.data.labels = d.uploads7.labels;
            chChart.data.datasets[0].data = d.uploads7.data;
            chChart.update();
            chvChart.data.labels = d.views30.labels;
            chvChart.data.datasets[0].data = d.views30.data;
            chvChart.update();
            document.getElementById('statsUpdated').textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
        } catch (e) { /* diam: data terakhir tetap tampil */ }
    }
    document.getElementById('refreshStats').addEventListener('click', refreshStats);
    setInterval(refreshStats, 60000);
});
</script>