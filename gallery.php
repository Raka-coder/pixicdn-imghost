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
    $stmt = getDB()->prepare("SELECT id, filename, public_url, thumbnail_url, views, size, user_id, uploaded_at FROM images WHERE $where ORDER BY $order LIMIT $per OFFSET $off");
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
$confirm_modal = true;
$rename_errors = ['invalid' => 'Nama file tidak valid.', 'taken' => 'Nama file sudah dipakai.', 'extension' => 'Ekstensi file harus tetap sama.', 'notfound' => 'Gambar tidak ditemukan.', 'limited' => 'Terlalu banyak rename. Coba lagi nanti.', 'failed' => 'Rename gagal. Coba lagi.'];
$rename_error = $rename_errors[$_GET['rename_error'] ?? ''] ?? '';
?>
<?php
$page_title = 'Gallery — PixiCDN';
require __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar Desktop -->
        <aside class="col-md-3 col-lg-2 d-none d-md-block bg-white border-end border-2 border-primary min-vh-100 position-sticky top-0 sidebar-desktop">
            <?php require __DIR__ . '/includes/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <main id="main-content" class="col-md-9 ms-sm-auto col-lg-10 px-4 py-5">
            <!-- Breadcrumb + Back Button -->
            <?php render_breadcrumbs($crumbs); ?>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <h1 class="page-title mb-0">Gallery</h1>
                <a href="/upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload me-1"></i>Upload</a>
            </div>
            <?php if ($rename_error !== ''): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= sanitize($rename_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
            <?php endif; ?>

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
                    <div class="d-flex justify-content-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle mb-3 icon-box-lg">
                            <i class="bi bi-images fs-1"></i>
                        </div>
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
                                    <button type="button" class="gallery-thumb p-0 border-0 bg-transparent w-100 h-100"
                                        data-bs-toggle="modal" data-bs-target="#imagePreviewModal"
                                        data-image-url="<?= sanitize($img['public_url']) ?>"
                                        data-image-name="<?= sanitize($img['filename']) ?>"
                                        data-image-size="<?= sanitize(format_bytes((int) $img['size'])) ?>"
                                        data-image-views="<?= number_format((int) $img['views']) ?>"
                                        data-image-date="<?= sanitize(format_tanggal_id((string) $img['uploaded_at'])) ?>"
                                        data-detail-url="/image.php?f=<?= sanitize($img['filename']) ?>"
                                        aria-label="Pratinjau <?= sanitize($img['filename']) ?>">
                                        <img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>"
                                             alt="<?= sanitize($img['filename']) ?>"
                                             loading="lazy"
                                             class="w-100 h-100 object-fit-cover zoom-hover">
                                    </button>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-dark bg-opacity-75 text-light px-2 py-1"><i class="bi bi-eye me-1"></i><?= (int) $img['views'] ?></span>
                                    </div>
                                </div>
                                <div class="card-body p-3 d-flex justify-content-between align-items-center gap-2">
                                    <p class="text-truncate small text-secondary mb-0"><?= sanitize($img['filename']) ?></p>
                                    <div class="btn-group btn-group-sm flex-shrink-0" role="group" aria-label="Aksi gambar">
                                        <button type="button" class="btn btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#renameModal"
                                            data-image-id="<?= (int) $img['id'] ?>"
                                            data-image-name="<?= sanitize($img['filename']) ?>"
                                            aria-label="Rename <?= sanitize($img['filename']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#confirmModal"
                                            data-confirm-title="Hapus gambar?"
                                            data-confirm-message="Gambar <?= sanitize($img['filename']) ?> akan dihapus permanen dan tidak bisa dikembalikan."
                                            data-confirm-action="/delete.php"
                                            data-confirm-fields='<?= sanitize(json_encode(['id' => (int) $img['id']])) ?>'
                                            data-confirm-submit="Hapus"
                                            aria-label="Hapus gambar <?= sanitize($img['filename']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <nav aria-label="Pagination" class="d-flex justify-content-center">
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

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="previewLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="previewLabel"><i class="bi bi-image me-2"></i>Preview Gambar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <img id="previewImg" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Pratinjau gambar" class="img-fluid rounded shadow-sm preview-img">
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold" for="previewFilename">Nama File</label>
                        <div class="input-group">
                            <input type="text" id="previewFilename" class="form-control bg-light" readonly value="">
                            <button class="btn btn-outline-primary" type="button" data-copy-target="previewFilename" data-copy-toast="1" data-bs-toggle="tooltip" data-bs-title="Salin nama file" aria-label="Salin nama file"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold" for="previewUrl">Direct URL</label>
                        <div class="input-group">
                            <input type="text" id="previewUrl" class="form-control bg-light mono small" readonly value="">
                            <button class="btn btn-outline-primary" type="button" data-copy-target="previewUrl" data-copy-toast="1" data-bs-toggle="tooltip" data-bs-title="Salin URL" aria-label="Salin URL"><i class="bi bi-clipboard"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary fw-semibold" for="previewSize">Ukuran</label>
                        <input type="text" id="previewSize" class="form-control bg-light" readonly value="">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary fw-semibold" for="previewViews">Total Views</label>
                        <input type="text" id="previewViews" class="form-control bg-light" readonly value="">
                    </div>
                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold" for="previewDate">Tanggal Upload</label>
                        <input type="text" id="previewDate" class="form-control bg-light" readonly value="">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="previewDetailLink" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Detail</a>
            </div>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="/rename.php" method="post" class="needs-validation" novalidate>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="renameLabel"><i class="bi bi-pencil me-2"></i>Rename Gambar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="image_id" id="renameImageId" value="">
                    <div class="mb-3">
                        <label for="newFilename" class="form-label fw-semibold">Nama File Baru</label>
                        <input type="text" class="form-control" id="newFilename" name="new_filename" required pattern="[a-zA-Z0-9_\-\.]+" maxlength="255" placeholder="contoh: foto-liburan-2026.jpg">
                        <div class="invalid-feedback">Hanya huruf, angka, underscore, dash, dan titik. Ekstensi harus tetap sama.</div>
                        <div class="form-text text-secondary small mt-1">Ekstensi file harus tetap sama agar gambar tetap valid.</div>
                    </div>
                    <div class="alert alert-info small mb-0"><i class="bi bi-info-circle me-1"></i>URL gambar akan otomatis diperbarui setelah rename.</div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
<script>
document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
        const modal = document.getElementById('imagePreviewModal');
        modal.querySelector('#previewImg').src = thumb.dataset.imageUrl;
        modal.querySelector('#previewImg').alt = thumb.dataset.imageName;
        modal.querySelector('#previewFilename').value = thumb.dataset.imageName;
        modal.querySelector('#previewUrl').value = thumb.dataset.imageUrl;
        modal.querySelector('#previewSize').value = thumb.dataset.imageSize;
        modal.querySelector('#previewViews').value = thumb.dataset.imageViews;
        modal.querySelector('#previewDate').value = thumb.dataset.imageDate;
        modal.querySelector('#previewDetailLink').href = thumb.dataset.detailUrl;
    });
});
document.querySelectorAll('[data-bs-target="#renameModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('renameImageId').value = btn.dataset.imageId;
        document.getElementById('newFilename').value = btn.dataset.imageName;
    });
});
</script>