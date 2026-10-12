<?php
/**
 * ============================================================
 * File: dashboard/api-key.php
 * Deskripsi: Kelola API key (tampil sekali saja) — Bootstrap 5.3
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
require_once dirname(__DIR__) . '/includes/api.php';

require_login();
$user = current_user();
start_secure_session();
$plain = $_SESSION['api_key_plain'] ?? '';
unset($_SESSION['api_key_plain']);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'generate' || $action === 'regenerate') {
                $_SESSION['api_key_plain'] = rotate_api_key((int) $user['id']);
                header('Location: /dashboard/api-key.php?done=1');
                exit;
            } elseif ($action === 'revoke') {
                revoke_api_key((int) $user['id']);
                $msg = 'API key dihapus.';
            }
        } catch (PDOException $e) {
            error_log('API key gagal: ' . $e->getMessage());
            $msg = 'Terjadi kesalahan pada sistem.';
        }
    }
}
$stmt = getDB()->prepare('SELECT api_key, api_key_created_at FROM users WHERE id = ?');
$stmt->execute([(int) $user['id']]);
$row = $stmt->fetch();
$has_key = !empty($row['api_key']);
$masked = $has_key ? '••••••••' . substr((string) $row['api_key'], -6) : '';
$active = 'api-key';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'API Key']];
$back_url = null;
?>
<?php
$page_title = 'API Key — PixiCDN';
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
            <!-- Breadcrumb -->
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

            <h1 class="page-title mb-4">API Key</h1>
            <p class="text-secondary small mb-4">Pakai di header <code class="mono small bg-body-tertiary border rounded px-2 py-1">X-API-Key</code>. Key hanya ditampilkan sekali setelah generate.</p>

            <?php if ($plain !== ''): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading fw-semibold mb-1">Salin sekarang, tidak bisa dilihat lagi:</h5>
                    <div class="d-flex gap-2 mt-2">
                        <input type="text" id="newApiKey" readonly value="<?= sanitize($plain) ?>" class="form-control mono small" aria-label="API Key baru">
                        <button class="btn btn-outline-primary" type="button" data-copy-target="newApiKey" data-bs-toggle="tooltip" data-bs-title="Salin ke clipboard" aria-label="Salin API Key">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>

            <?php if ($msg !== ''): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= sanitize($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endif; ?>

            <div class="card card-trust mb-4">
                <div class="card-body">
                    <p class="mb-2">Status: <span class="fw-semibold <?= $has_key ? 'text-success' : 'text-secondary' ?>"><?= $has_key ? 'Aktif (' . sanitize($masked) . ')' : 'Belum ada' ?></span></p>
                    <?php if ($has_key): ?>
                        <p class="text-secondary small mb-0">Dibuat: <?= sanitize((string) ($row['api_key_created_at'] ?? '-')) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="post" class="d-flex gap-2 mb-3">
                <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                <input type="hidden" name="action" value="<?= $has_key ? 'regenerate' : 'generate' ?>">
                <button type="submit" class="btn btn-primary"><i class="bi bi-<?= $has_key ? 'arrow-clockwise' : 'plus-lg' ?> me-1"></i><?= $has_key ? 'Regenerate' : 'Generate' ?></button>
            </form>

            <?php if ($has_key): ?>
                <form method="post" class="d-flex gap-2" onsubmit="return confirm('Hapus API key?')">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="action" value="revoke">
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
                </form>
            <?php endif; ?>

            <!-- API Documentation Card -->
            <div class="card card-trust mt-5">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="fw-semibold mb-0">Contoh Penggunaan</h5>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">Upload gambar via API:</p>
                    <pre class="mono small bg-dark text-light rounded-3 p-3 overflow-auto mb-3"><code>curl -H "X-API-Key: <key>" \
  -F "image=@foto.jpg" \
  https://pixicdn.erdtreeproject.my.id/api/upload.php</code></pre>
                    <p class="text-secondary small mb-3">Response JSON:</p>
                    <pre class="mono small bg-body-tertiary border rounded-3 p-3 overflow-auto"><code>{
  "success": true,
  "data": [{
    "url": "https://pixicdn.erdtreeproject.my.id/abc123.jpg",
    "bbcode": "[img]https://pixicdn.erdtreeproject.my.id/abc123.jpg[/img]",
    "html": "<img src=\"https://pixicdn.erdtreeproject.my.id/abc123.jpg\">"
  }]
}</code></pre>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>