<?php
/**
 * ============================================================
 * File: auth/register.php
 * Deskripsi: Registrasi user + tampilkan recovery code sekali — Bootstrap 5.3
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
$error = '';
$recovery_code = $_SESSION['just_registered_code'] ?? '';
unset($_SESSION['just_registered_code']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid. Muat ulang halaman.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        try {
            $allowed = check_rate_limit('register:' . $ip, 'register', 5);
        } catch (PDOException $e) {
            error_log('Register rate gagal: ' . $e->getMessage());
            $allowed = true;
        }
        if (!$allowed) {
            $error = 'Terlalu banyak percobaan daftar. Coba lagi nanti.';
        } else {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            if ($password !== $confirm) {
                $error = 'Konfirmasi password tidak sama.';
            } else {
                try {
                    $result = register_user($username, $password);
                    mark_login($result['user_id']);
                    $_SESSION['just_registered_code'] = $result['recovery_code'];
                    header('Location: /auth/register.php?done=1');
                    exit;
                } catch (RuntimeException $e) {
                    $error = $e->getMessage();
                } catch (PDOException $e) {
                    error_log('Register gagal: ' . $e->getMessage());
                    $error = 'Terjadi kesalahan pada sistem. Silakan coba lagi.';
                }
            }
        }
    }
}
$just_done = isset($_GET['done']) && $recovery_code !== '';
?>
<?php
$page_title = 'Daftar — PixiCDN';
$is_auth_page = true;
require dirname(__DIR__) . '/includes/header.php';
?>

<main class="flex-grow-1 d-flex align-items-center justify-content-center py-5 px-3">
    <div class="w-100 auth-card">
        <div class="text-center mb-4">
            <a href="/" class="fw-bold fs-4 text-primary text-decoration-none"><i class="bi bi-images me-1"></i>PixiCDN</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="fw-semibold mb-4 text-center auth-title">Daftar akun gratis</h1>

                <?php if ($just_done): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading fw-semibold mb-1">Simpan recovery code ini sekarang!</h5>
                        <p class="mono fw-semibold fs-3 mb-1"><?= sanitize($recovery_code) ?></p>
                        <p class="text-secondary small mb-3">Ditampilkan sekali saja. Dipakai untuk reset password.</p>
                        <a href="/dashboard/" class="btn btn-primary w-100">Ke Dashboard</a>
                    </div>
                <?php else: ?>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= sanitize($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">

                        <div class="form-floating mb-3">
                            <input type="text" name="username" id="username" class="form-control" placeholder="Username" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+" autocomplete="username">
                            <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                            <div class="invalid-feedback">Username 3-30 karakter, huruf/angka/underscore.</div>
                        </div>

                        <div class="form-floating mb-3 position-relative">
                            <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Password" required minlength="8" autocomplete="new-password">
                            <label for="password"><i class="bi bi-lock me-2"></i>Password (min 8 karakter)</label>
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

                        <div class="form-floating mb-3">
                            <input type="password" name="confirm" id="confirm" class="form-control" placeholder="Konfirmasi Password" required autocomplete="new-password">
                            <label for="confirm"><i class="bi bi-lock-fill me-2"></i>Konfirmasi Password</label>
                            <div class="invalid-feedback">Konfirmasi password tidak sama.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Daftar</button>
                    </form>

                    <p class="text-center text-secondary small mt-4">
                        <a href="/auth/login.php" class="text-primary text-decoration-none">Sudah punya akun? Masuk</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <p class="text-center mt-4 mb-0">
            <a href="/" class="text-secondary text-decoration-none small">← Kembali ke Beranda</a>
        </p>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>