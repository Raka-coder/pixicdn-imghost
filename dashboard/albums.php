<?php
/**
 * ============================================================
 * File: dashboard/albums.php
 * Deskripsi: List + buat album (login) — Bootstrap 5.3
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
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Token keamanan tidak valid.';
    } else {
        try {
            create_album((int) $user['id'], $_POST['name'] ?? '', $_POST['description'] ?? null ?: null);
            redirect('/dashboard/albums.php?ok=1');
        } catch (RuntimeException $e) {
            $err = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Album buat gagal: ' . $e->getMessage());
            $err = 'Terjadi kesalahan pada sistem.';
        }
    }
}
$stmt = getDB()->prepare('SELECT * FROM albums WHERE user_id = ? AND is_deleted = FALSE ORDER BY created_at DESC');
$stmt->execute([(int) $user['id']]);
$albums = $stmt->fetchAll();
$active = 'album';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'Album']];
$is_dashboard = true;
?>
<?php
$page_title = 'Album — PixiCDN';
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
            <?php render_breadcrumbs($crumbs); ?>

            <h1 class="page-title mb-4">Album Saya</h1>

            <?php if ($err !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <?= sanitize($err) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>

            <!-- Create Album Form -->
            <form method="post" class="card card-trust mb-4">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required maxlength="100" placeholder="Nama album">
                        </div>
                        <div class="col-md-6">
                            <label for="description" class="form-label">Deskripsi</label>
                            <input type="text" name="description" id="description" class="form-control" maxlength="500" placeholder="Deskripsi album (opsional)">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat</button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Albums Grid -->
            <?php if ($albums): ?>
                <div class="row g-4">
                    <?php foreach ($albums as $a): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <a href="/dashboard/album.php?id=<?= (int) $a['id'] ?>" class="card card-trust h-100 text-decoration-none d-block">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 icon-box">
                                            <i class="bi bi-folder fs-4"></i>
                                        </div>
                                        <div>
                                            <h3 class="fw-semibold mb-0"><?= sanitize($a['name']) ?></h3>
                                            <p class="text-secondary small mb-0"><?= (int) $a['image_count'] ?> gambar · <?= $a['is_public'] ? 'publik' : 'privat' ?></p>
                                        </div>
                                    </div>
                                    <?php if ($a['description']): ?>
                                        <p class="text-secondary small mt-auto"><?= sanitize($a['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="card card-trust text-center py-5">
                    <div class="d-flex justify-content-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle mb-3 icon-box-lg">
                            <i class="bi bi-folder fs-1"></i>
                        </div>
                    </div>
                    <h3 class="fw-semibold mb-2">Belum ada album</h3>
                    <p class="text-secondary mb-4">Buat album pertama Anda menggunakan form di atas.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>