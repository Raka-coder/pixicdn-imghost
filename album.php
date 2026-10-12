<?php
/**
 * ============================================================
 * File: album.php
 * Deskripsi: Viewer album publik via slug (tanpa login jika publik) — Bootstrap 5.3
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * URL: /album.php?u=<username>&s=<slug>
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
require_once __DIR__ . '/includes/album.php';

$username = trim($_GET['u'] ?? '');
$slug = trim($_GET['s'] ?? '');
if ($username === '' || $slug === '') {
    http_response_code(404);
    exit('Album tidak ditemukan');
}

start_secure_session();
$me = null;
try {
    $me = current_user();
} catch (PDOException $e) {
    error_log('Album publik user gagal: ' . $e->getMessage());
}

try {
    $stmt = getDB()->prepare('SELECT a.* FROM albums a JOIN users u ON u.id = a.user_id WHERE u.username = ? AND a.slug = ? AND a.is_deleted = FALSE');
    $stmt->execute([$username, $slug]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Album publik gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada sistem.');
}
if (!$row) {
    http_response_code(404);
    exit('Album tidak ditemukan');
}

$is_owner = $me !== null && (int) $row['user_id'] === (int) $me['id'];
if (!$is_owner && !(bool) $row['is_public']) {
    http_response_code(404);
    exit('Album tidak ditemukan');
}
$album = get_album((int) $row['id'], $me !== null ? (int) $me['id'] : 0, allow_public: true) ?? $row;

try {
    $stmt = getDB()->prepare('SELECT i.filename, i.public_url, i.thumbnail_url FROM images i JOIN album_images ai ON ai.image_id = i.id WHERE ai.album_id = ? AND i.is_deleted = FALSE AND (i.expires_at IS NULL OR i.expires_at > NOW()) ORDER BY ai.position, ai.added_at DESC');
    $stmt->execute([(int) $album['id']]);
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Album publik items gagal: ' . $e->getMessage());
    $items = [];
}
?>
<?php
$page_title = sanitize($album['name']) . ' — PixiCDN';
$is_public_page = true;
require dirname(__DIR__) . '/includes/header.php';
?>

<main class="flex-grow-1">
    <div class="container py-5">
        <!-- Breadcrumb for public album -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/gallery.php" class="text-decoration-none text-secondary">Gallery</a></li>
                <li class="breadcrumb-item"><i class="bi bi-chevron-right text-secondary"></i></li>
                <li class="breadcrumb-item active fw-semibold text-body-emphasis" aria-current="page"><?= sanitize($album['name']) ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1 class="page-title mb-1"><?= sanitize($album['name']) ?></h1>
                <p class="text-secondary small mb-0"><span class="tnum"><?= (int) $album['image_count'] ?> gambar</span> · oleh <?= sanitize($username) ?></p>
                <?php if ($album['description']): ?>
                    <p class="text-secondary small mt-2"><?= sanitize($album['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$items): ?>
            <!-- Empty State -->
            <div class="card card-trust text-center py-5">
                <div class="d-inline-flex align-items-center justify-content-center bg-secondary-subtle text-secondary rounded-circle mb-3 icon-box-lg">
                    <i class="bi bi-folder fs-1"></i>
                </div>
                <h3 class="fw-semibold mb-2">Album ini kosong</h3>
                <p class="text-secondary mb-0">Belum ada gambar di album ini.</p>
            </div>
        <?php else: ?>
            <!-- Album Grid -->
            <div class="row g-4">
                <?php foreach ($items as $img): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="/image.php?f=<?= sanitize($img['filename']) ?>" class="card card-trust h-100 overflow-hidden text-decoration-none">
                            <div class="ratio ratio-4x3 overflow-hidden">
                                <img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>" 
                                     alt="<?= sanitize($img['filename']) ?>" 
                                     loading="lazy" 
                                     class="w-100 h-100 object-fit-cover zoom-hover">
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="/gallery.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Gallery</a>
        </div>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>