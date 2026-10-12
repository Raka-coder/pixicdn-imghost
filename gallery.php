<?php
/**
 * ============================================================
 * File: gallery.php
 * Deskripsi: Gallery pribadi user login (filter, sort, pagination 20) — Bootstrap 5.3
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

start_secure_session();
require_login();
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
<?php
$page_title = 'Gallery — PixiCDN';
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

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h1 class="page-title mb-0">Gallery</h1>
                <a href="/upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
            </div>

            <!-- Filter & Sort Form -->
            <form method="get" class="row g-2 align-items-end mb-4">
                <div class="col-auto">
                    <label for="filter" class="visually-hidden">Filter waktu</label>
                    <select name="filter" id="filter" class="form-select" aria-label="Filter waktu">
                        <?php foreach (['all' => 'Semua', 'today' => 'Hari ini', 'week' => 'Minggu ini', 'month' => 'Bulan ini'] as $k => $l): ?>
                            <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label for="sort" class="visually-hidden">Urutkan</label>
                    <select name="sort" id="sort" class="form-select" aria-label="Urutkan">
                        <?php foreach (['newest' => 'Terbaru', 'oldest' => 'Terlama', 'views' => 'Terpopuler'] as $k => $l): ?>
                            <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">Terapkan</button>
                </div>
            </form>

            <?php if (!$images): ?>
                <!-- Empty State -->
                <div class="card card-trust text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle mb-3 icon-box-lg">
                        <i class="bi bi-images fs-1"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">Belum ada gambar</h3>
                    <p class="text-secondary mb-4">Upload gambar pertama Anda untuk memulai</p>
                    <a href="/upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Upload Sekarang</a>
                </div>
            <?php else: ?>
                <!-- Gallery Grid -->
                <div class="row g-4 mb-4">
                    <?php foreach ($images as $img): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card card-trust h-100 overflow-hidden">
                                <div class="ratio ratio-4x3 overflow-hidden position-relative">
                                    <a href="/image.php?f=<?= sanitize($img['filename']) ?>">
                                        <img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>" 
                                             alt="<?= sanitize($img['filename']) ?>" 
                                             loading="lazy" 
                                             class="w-100 h-100 object-fit-cover zoom-hover">
                                    </a>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-dark bg-opacity-75 text-light px-2 py-1"><i class="bi bi-eye me-1"></i><?= (int) $img['views'] ?></span>
                                    </div>
                                </div>
                                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                    <p class="text-truncate small text-secondary mb-0"><?= sanitize($img['filename']) ?></p>
                                    <form method="post" action="/delete.php" onsubmit="return confirm('Hapus gambar ini?')" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" data-bs-title="Hapus gambar" aria-label="Hapus gambar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <nav aria-label="Pagination" class="d-flex justify-center">
                    <ul class="pagination pagination-sm">
                        <?php for ($p = 1; $p <= $pages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>>
                                <a class="page-link" href="?filter=<?= sanitize($filter) ?>&sort=<?= sanitize($sort) ?>&page=<?= $p ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>