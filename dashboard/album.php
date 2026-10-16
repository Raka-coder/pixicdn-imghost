<?php
/**
 * ============================================================
 * File: dashboard/album.php
 * Deskripsi: Detail album (tambah/hapus gambar, cover, hapus album) — Bootstrap 5.3
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
require_once dirname(__DIR__) . '/includes/album.php';

require_login();
$user = current_user();
$uid = (int) $user['id'];
$album = get_album((int) ($_GET['id'] ?? 0), $uid);
if ($album === null) {
    http_response_code(404);
    exit('Album tidak ditemukan');
}
$aid = (int) $album['id'];
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                $stmt = getDB()->prepare('SELECT id FROM images WHERE filename = ? AND user_id = ? AND is_deleted = FALSE');
                $stmt->execute([trim($_POST['filename'] ?? ''), $uid]);
                $img = $stmt->fetch();
                if (!$img) {
                    $err = 'Gambar tidak ditemukan atau bukan milik Anda.';
                } elseif (!add_to_album($aid, (int) $img['id'], $uid)) {
                    $err = 'Gagal menambah ke album.';
                } else {
                    $msg = 'Gambar ditambahkan.';
                }
            } elseif ($action === 'remove') {
                remove_from_album($aid, (int) ($_POST['image_id'] ?? 0), $uid);
                $msg = 'Gambar dikeluarkan.';
            } elseif ($action === 'cover') {
                $stmt = getDB()->prepare('SELECT id FROM images WHERE id = ? AND user_id = ? AND is_deleted = FALSE');
                $stmt->execute([(int) ($_POST['image_id'] ?? 0), $uid]);
                if ($stmt->fetch()) {
                    getDB()->prepare('UPDATE albums SET cover_image_id = ? WHERE id = ? AND user_id = ?')->execute([(int) $_POST['image_id'], $aid, $uid]);
                    $msg = 'Sampul diperbarui.';
                }
            } elseif ($action === 'delete') {
                getDB()->prepare('UPDATE albums SET is_deleted = TRUE WHERE id = ? AND user_id = ?')->execute([$aid, $uid]);
                redirect('/dashboard/albums.php?deleted=1');
            }
            $album = get_album($aid, $uid);
        } catch (PDOException $e) {
            error_log('Album detail gagal: ' . $e->getMessage());
            $err = 'Terjadi kesalahan pada sistem.';
        }
    }
}
$stmt = getDB()->prepare('SELECT i.* FROM images i JOIN album_images ai ON ai.image_id = i.id WHERE ai.album_id = ? AND i.is_deleted = FALSE ORDER BY ai.position, ai.added_at DESC');
$stmt->execute([$aid]);
$items = $stmt->fetchAll();
$share_url = rtrim(APP_URL, '/') . '/album.php?u=' . rawurlencode($user['username']) . '&s=' . rawurlencode($album['slug']);
$active = 'album';
$crumbs = [
    ['label' => 'Dashboard', 'url' => '/dashboard/'],
    ['label' => 'Album', 'url' => '/dashboard/albums.php'],
    ['label' => $album['name']],
];
$confirm_modal = true;
$is_dashboard = true;
?>
<?php
$page_title = sanitize($album['name']) . ' — PixiCDN';
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
            <!-- Breadcrumb (hierarchy: Dashboard > Album > [Nama Album]) -->
            <?php render_breadcrumbs($crumbs); ?>

            <h1 class="page-title mb-2"><?= sanitize($album['name']) ?></h1>
            <p class="text-secondary small mb-4"><?= (int) $album['image_count'] ?> gambar · <?= $album['is_public'] ? 'publik' : 'privat' ?></p>

            <!-- Share Link -->
            <div class="card card-trust mb-4">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <div class="flex-grow-1">
                        <p class="text-secondary small mb-1">Link publik album</p>
                        <div class="input-group input-group-sm">
                            <input type="text" id="shareUrl" readonly value="<?= sanitize($share_url) ?>" class="form-control mono small" aria-label="Link publik album">
                            <button class="btn btn-outline-primary" type="button" data-copy-target="shareUrl" data-bs-toggle="tooltip" data-bs-title="Salin link" aria-label="Salin link publik album">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= sanitize($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>
            <?php if ($err !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= sanitize($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>

            <!-- Add Image Form -->
            <!-- Add Image Form -->
            <form method="post" class="card card-trust mb-4">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3 align-items-end">
                        <div class="col">
                            <label for="filename" class="form-label visually-hidden">Nama file gambar</label>
                            <input type="text" name="filename" id="filename" class="form-control" required placeholder="nama-file.jpg (milik Anda)" aria-label="Nama file gambar">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Album Images Grid -->
            <?php if ($items): ?>
                <div class="row g-4 mb-4">
                    <?php foreach ($items as $img): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card card-trust h-100 overflow-hidden position-relative">
                                <div class="ratio ratio-4x3 overflow-hidden">
                                    <button type="button" class="p-0 border-0 bg-transparent w-100 h-100" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-image-url="<?= sanitize($img['public_url']) ?>" data-image-title="<?= sanitize($img['filename']) ?>" aria-label="Pratinjau <?= sanitize($img['filename']) ?>">
                                        <img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>"
                                             alt="<?= sanitize($img['filename']) ?>"
                                             loading="lazy"
                                             class="w-100 h-100 object-fit-cover">
                                    </button>
                                </div>
                                <div class="card-body p-2 d-flex gap-2">
                                    <form method="post" class="flex-grow-1">
                                        <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="cover">
                                        <input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="tooltip" data-bs-title="Jadikan sampul" aria-label="Jadikan sampul <?= sanitize($img['filename']) ?>"><i class="bi bi-image me-1"></i>Sampul</button>
                                    </form>
                                    <span class="flex-grow-1" data-bs-toggle="tooltip" data-bs-title="Keluarkan dari album">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                            data-bs-toggle="modal" data-bs-target="#confirmModal"
                                            data-confirm-title="Keluarkan gambar?"
                                            data-confirm-message="Gambar <?= sanitize($img['filename']) ?> dikeluarkan dari album ini. File tidak ikut terhapus."
                                            data-confirm-action=""
                                            data-confirm-fields='<?= sanitize(json_encode(['action' => 'remove', 'image_id' => (int) $img['id']])) ?>'
                                            data-confirm-submit="Keluarkan"
                                            aria-label="Keluarkan <?= sanitize($img['filename']) ?> dari album"><i class="bi bi-box-arrow-right me-1"></i>Keluarkan</button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card card-trust text-center py-5 mb-4">
                    <div class="d-flex justify-content-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle mb-3 icon-box-lg">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    </div>
                    <h3 class="fw-semibold mb-2">Album ini kosong</h3>
                    <p class="text-secondary mb-3">Tambahkan gambar pertama menggunakan form di atas.</p>
                </div>
            <?php endif; ?>

            <!-- Delete Album -->
            <div class="card card-trust border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h3 class="fw-semibold text-danger mb-1">Hapus Album</h3>
                            <p class="text-secondary small mb-0">Album akan dihapus permanen. Gambar di dalamnya tidak terhapus.</p>
                        </div>
                        <button type="button" class="btn btn-danger"
                            data-bs-toggle="modal" data-bs-target="#confirmModal"
                            data-confirm-title="Hapus album?"
                            data-confirm-message="Album <?= sanitize($album['name']) ?> akan dihapus permanen. Gambar di dalamnya tidak ikut terhapus."
                            data-confirm-action=""
                            data-confirm-fields='<?= sanitize(json_encode(['action' => 'delete'])) ?>'
                            data-confirm-submit="Hapus album"><i class="bi bi-trash me-1"></i>Hapus album</button>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="imagePreviewModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <img id="imagePreviewImg" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="Pratinjau gambar" class="img-fluid rounded mh-70">
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Copy share URL
    const shareCopy = document.getElementById('share-copy');
    if (shareCopy) {
        shareCopy.addEventListener('click', async function () {
            await navigator.clipboard.writeText(this.dataset.url);
            this.textContent = 'Tersalin!';
            setTimeout(() => this.textContent = 'Salin link', 1500);
        });
    }
});
</script>