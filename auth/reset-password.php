<?php
/**
 * ============================================================
 * File: auth/reset-password.php
 * Deskripsi: Set password baru dengan token sesi — Bootstrap 5.3
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
$token = $_SESSION['reset_token_plain'] ?? '';
if ($token === '') {
    redirect('/auth/forgot-password.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } elseif (($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (reset_password_with_token($token, $_POST['password'] ?? '')) {
        unset($_SESSION['reset_token_plain'], $_SESSION['reset_user_id']);
        redirect('/auth/login.php?reset=1');
    } else {
        $error = 'Token kedaluwarsa atau password tidak valid (min 8).';
    }
}
?>
<?php
$page_title = 'Reset Password — PixiCDN';
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
                <h1 class="fw-semibold mb-4 text-center auth-title">Password Baru</h1>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= sanitize($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Password baru" required minlength="8" autocomplete="new-password">
                        <label for="password"><i class="bi bi-lock me-2"></i>Password baru (min 8)</label>
                        <button type="button" class="btn btn-link text-secondary p-0 position-absolute end-0 top-50 translate-middle-y me-3" data-bs-toggle="password" data-bs-target="password" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                        <div class="invalid-feedback">Password minimal 8 karakter.</div>
                    </div>

                    <!-- Password Strength Meter -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-secondary" id="passwordStrengthLabel">Kekuatan: —</span>
                        </div>
                        <div class="progress progress-strength">
                            <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" id="passwordStrength" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" name="confirm" id="confirm" class="form-control pe-5" placeholder="Konfirmasi" required autocomplete="new-password">
                        <label for="confirm"><i class="bi bi-lock-fill me-2"></i>Konfirmasi</label>
                        <button type="button" class="btn btn-link text-secondary p-0 position-absolute end-0 top-50 translate-middle-y me-3" data-bs-toggle="password" data-bs-target="confirm" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                        <div class="invalid-feedback">Konfirmasi password tidak sama.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Simpan</button>
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