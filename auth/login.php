<?php
/**
 * ============================================================
 * File: auth/login.php
 * Deskripsi: Login user dengan session regenerate — Bootstrap 5.3
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
require_once dirname(__DIR__) . '/includes/upload.php';

start_secure_session();
if (current_user() !== null) {
    redirect('/dashboard/');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        try {
            $allowed = check_rate_limit('login:' . $ip, 'login', 5, 900);
        } catch (PDOException $e) {
            error_log('Login rate gagal: ' . $e->getMessage());
            $allowed = true;
        }
        if (!$allowed) {
            http_response_code(429);
            $error = 'Terlalu banyak percobaan. Coba lagi nanti.';
        } else {
            $user = verify_login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
            if ($user === null) {
                $error = 'Username atau password salah.';
            } else {
                try {
                    getDB()->prepare('DELETE FROM rate_limits WHERE identifier = ? AND action = ?')->execute(['login:' . $ip, 'login']);
                } catch (PDOException $e) {
                    error_log('Login rate reset gagal: ' . $e->getMessage());
                }
                mark_login((int) $user['id']);
                redirect('/dashboard/');
            }
        }
    }
}
?>
<?php
$page_title = 'Masuk — PixiCDN';
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
                <h1 class="fw-semibold mb-4 text-center auth-title">Selamat datang kembali</h1>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= sanitize($error) ?>
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

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Password" required autocomplete="current-password">
                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                        <button type="button" class="btn btn-link text-secondary p-0 position-absolute end-0 top-50 translate-middle-y me-3" data-bs-toggle="password" data-bs-target="password" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                        <div class="invalid-feedback">Password wajib diisi.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">Masuk</button>
                </form>

                <p class="text-center text-secondary small mt-4">
                    <a href="/auth/forgot-password.php" class="text-primary text-decoration-none">Lupa password?</a> · 
                    <a href="/auth/register.php" class="text-primary text-decoration-none">Daftar</a>
                </p>
            </div>
        </div>
        <p class="text-center mt-4 mb-0">
            <a href="/" class="text-secondary text-decoration-none small">← Kembali ke Beranda</a>
        </p>
    </div>
</main>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>