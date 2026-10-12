<?php
/**
 * ============================================================
 * File: image.php
 * Deskripsi: Direct link viewer + view counter (1 IP/jam) — Bootstrap 5.3
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

$filename = basename($_GET['f'] ?? '');
if ($filename === '') {
    http_response_code(404);
    exit('Gambar tidak ditemukan');
}
try {
    $stmt = getDB()->prepare('SELECT * FROM images WHERE filename = ? AND is_deleted = FALSE');
    $stmt->execute([$filename]);
    $img = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Viewer gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada sistem.');
}
if (!$img || ($img['expires_at'] !== null && strtotime((string) $img['expires_at']) < time())) {
    http_response_code(404);
    exit('Gambar tidak ditemukan');
}

// View counter: 1 IP = 1 view/jam
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
try {
    $stmt = getDB()->prepare('SELECT id FROM view_logs WHERE image_id = ? AND ip_address = ? AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1');
    $stmt->execute([(int) $img['id'], $ip]);
    if (!$stmt->fetch()) {
        getDB()->prepare('UPDATE images SET views = views + 1 WHERE id = ?')->execute([(int) $img['id']]);
        getDB()->prepare('INSERT INTO view_logs (image_id, ip_address, user_agent, referrer) VALUES (?, ?, ?, ?)')->execute([
            (int) $img['id'], $ip, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000), mb_substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
        ]);
        $img['views'] = (int) $img['views'] + 1;
    }
} catch (PDOException $e) {
    error_log('View counter gagal: ' . $e->getMessage());
}

$url = (string) $img['public_url'];
?>
<?php
$page_title = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . ' — PixiCDN';
$is_public_page = true;
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow-1">
    <div class="container py-5">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/gallery.php" class="text-decoration-none text-secondary">Gallery</a></li>
                <li class="breadcrumb-item"><i class="bi bi-chevron-right text-secondary"></i></li>
                <li class="breadcrumb-item active fw-semibold text-body-emphasis" aria-current="page"><?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?></li>
            </ol>
        </nav>

        <div class="mx-auto mw-3xl">
            <div class="card card-trust text-center">
                <div class="card-body p-4 p-md-5">
                    <img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" 
                         alt="<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>" 
                         class="img-fluid rounded mb-4 border mh-70">
                    
                    <p class="text-secondary mb-4">
                        <span class="tnum"><?= (int) $img['views'] ?> views</span> · 
                        <?= htmlspecialchars((string) ($img['original_name'] ?? $filename), ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <div class="card card-trust bg-body-tertiary">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="fw-semibold mb-0">Format Link Siap Pakai</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small text-secondary">Direct Link</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="copyDirect" readonly value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="form-control mono small" aria-label="Direct Link">
                                        <button class="btn btn-outline-primary" type="button" data-copy-target="copyDirect" data-bs-toggle="tooltip" data-bs-title="Salin direct link" aria-label="Salin direct link">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-secondary">BBCode (Forum)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="copyBBCode" readonly value="[img]<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>[/img]" class="form-control mono small" aria-label="BBCode">
                                        <button class="btn btn-outline-primary" type="button" data-copy-target="copyBBCode" data-bs-toggle="tooltip" data-bs-title="Salin BBCode" aria-label="Salin BBCode">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small text-secondary">HTML</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="copyHTML" readonly value='<img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">' class="form-control mono small" aria-label="HTML">
                                        <button class="btn btn-outline-primary" type="button" data-copy-target="copyHTML" data-bs-toggle="tooltip" data-bs-title="Salin HTML" aria-label="Salin HTML">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="/gallery.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>Kembali ke Gallery</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>