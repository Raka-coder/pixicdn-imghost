<?php
/**
 * ============================================================
 * File: auth/forgot-password.php
 * Deskripsi: Request reset via recovery code (no enumeration) — Bootstrap 5.3
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

start_secure_session();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Token keamanan tidak valid.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        request_password_reset(trim($_POST['username'] ?? ''), trim($_POST['recovery_code'] ?? ''), $ip);
        $message = 'Jika data cocok, Anda bisa lanjut set password baru.';
        if (isset($_SESSION['reset_token_plain'])) {
            header('Location: /auth/reset-password.php');
            exit;
        }
    }
}
?>
<?php
$page_title = 'Lupa Password — PixiCDN';
$is_auth_page = true;
require dirname(__DIR__) . '/includes/header.php';
?>

<main id="main-content" class="flex-grow-1 d-flex align-items-center justify-content-center py-5 px-3">
    <div class="w-100 auth-card">
        <div class="text-center mb-4">
            <a href="/" class="fw-bold fs-4 text-primary text-decoration-none"><i class="bi bi-images me-1"></i>PixiCDN</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="fw-semibold mb-4 text-center auth-title">Lupa Password</h1>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= sanitize($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">

                    <div class="form-floating mb-3">
                        <input type="text" name="username" id="username" class="form-control" placeholder="Username" required autocomplete="username">
                        <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                        <div class="invalid-feedback">Username wajib diisi.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" name="recovery_code" id="recovery_code" class="form-control mono input-uppercase" placeholder="Recovery code" required minlength="8" maxlength="8" autocomplete="one-time-code">
                        <label for="recovery_code"><i class="bi bi-key me-2"></i>Recovery code (8 karakter)</label>
                        <div class="invalid-feedback">Recovery code 8 karakter wajib diisi.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Lanjut</button>
                </form>

                <p class="text-center text-secondary small mt-4">
                    <a href="/auth/login.php" class="text-primary text-decoration-none">← Kembali ke Masuk</a>
                </p>
            </div>
        </div>
        <p class="text-center mt-4 mb-0">
            <a href="/" class="text-secondary text-decoration-none small">← Kembali ke Beranda</a>
        </p>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>