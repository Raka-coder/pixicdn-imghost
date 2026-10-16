<?php
/**
 * ============================================================
 * File: auth/profile.php
 * Deskripsi: Info user + edit username + ubah password — Bootstrap 5.3
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

require_login();
$user = current_user();
$msg = '';
$err = '';
$active = 'profile';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'Profil']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'username') {
                $new = trim($_POST['username'] ?? '');
                if (!validate_username($new)) {
                    $err = 'Username 3-30 karakter, huruf/angka/underscore.';
                } else {
                    $stmt = getDB()->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
                    $stmt->execute([$new, (int) $user['id']]);
                    if ($stmt->fetch()) {
                        $err = 'Username sudah dipakai.';
                    } else {
                        getDB()->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$new, (int) $user['id']]);
                        $msg = 'Username diperbarui.';
                        $user = current_user();
                    }
                }
            } elseif ($action === 'password') {
                $stmt = getDB()->prepare('SELECT password_hash FROM users WHERE id = ?');
                $stmt->execute([(int) $user['id']]);
                $row = $stmt->fetch();
                if (!$row || !password_verify($_POST['old'] ?? '', $row['password_hash'])) {
                    $err = 'Password lama salah.';
                } elseif (($_POST['new'] ?? '') !== ($_POST['confirm'] ?? '')) {
                    $err = 'Konfirmasi password tidak sama.';
                } elseif (!validate_password($_POST['new'] ?? '')) {
                    $err = 'Password baru minimal 8 karakter.';
                } else {
                    getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([
                        password_hash($_POST['new'], PASSWORD_BCRYPT, ['cost' => 12]), (int) $user['id'],
                    ]);
                    $msg = 'Password diperbarui.';
                }
            }
        } catch (PDOException $e) {
            error_log('Profile gagal: ' . $e->getMessage());
            $err = 'Terjadi kesalahan pada sistem.';
        }
    }
}
?>
<?php
$page_title = 'Profil — PixiCDN';
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

            <h1 class="page-title mb-4">Profil</h1>

            <!-- Profile Info Card -->
            <div class="card card-trust mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center icon-box-lg">
                                <span class="fw-bold fs-2"><?= strtoupper(substr(sanitize($user['username']), 0, 1)) ?></span>
                            </div>
                        </div>
                        <div class="col">
                            <h3 class="fw-semibold mb-1"><?= sanitize($user['username']) ?></h3>
                            <p class="text-secondary small mb-0">Bergabung: <?= sanitize((string) $user['created_at']) ?></p>
                        </div>
                        <div class="col-auto">
                            <div class="text-end">
                                <p class="text-secondary small mb-1">Storage</p>
                                <p class="tnum fw-bold text-primary"><?= round(((int) $user['storage_used']) / 1048576, 2) ?> / <?= round(((int) $user['storage_quota']) / 1048576) ?> MB</p>
                            </div>
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

            <!-- Username Form -->
            <form method="post" class="card card-trust mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="fw-semibold mb-0">Ubah Username</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="action" value="username">
                    <div class="form-floating mb-3">
                        <input type="text" name="username" id="username" class="form-control" placeholder="Username" required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30" value="<?= sanitize($user['username']) ?>" autocomplete="username">
                        <label for="username"><i class="bi bi-person me-2"></i>Username baru</label>
                        <div class="invalid-feedback">Username 3-30 karakter, huruf/angka/underscore.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Simpan username</button>
                </div>
            </form>

            <!-- Password Form -->
            <form method="post" class="card card-trust">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="fw-semibold mb-0">Ubah Password</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <input type="hidden" name="action" value="password">
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" name="old" id="oldPassword" class="form-control pe-5" placeholder="Password lama" required autocomplete="current-password">
                        <label for="oldPassword"><i class="bi bi-lock me-2"></i>Password lama</label>
                        <button type="button" class="btn btn-link text-secondary p-0 position-absolute end-0 top-50 translate-middle-y me-3" data-bs-toggle="password" data-bs-target="oldPassword" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                        <div class="invalid-feedback">Password lama salah.</div>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" name="new" id="newPassword" class="form-control pe-5" placeholder="Password baru" required minlength="8" autocomplete="new-password">
                        <label for="newPassword"><i class="bi bi-lock-fill me-2"></i>Password baru (min 8)</label>
                        <button type="button" class="btn btn-link text-secondary p-0 position-absolute end-0 top-50 translate-middle-y me-3" data-bs-toggle="password" data-bs-target="newPassword" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                        <div class="invalid-feedback">Password baru minimal 8 karakter.</div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-secondary" id="passwordStrengthLabel">Kekuatan: —</span>
                        </div>
                        <div class="progress progress-strength">
                            <div class="progress-bar bg-danger progress-bar-striped progress-bar-animated" id="passwordStrength" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" name="confirm" id="confirmPassword" class="form-control pe-5" placeholder="Konfirmasi" required autocomplete="new-password">
                        <label for="confirmPassword"><i class="bi bi-lock-fill me-2"></i>Konfirmasi</label>
                        <button type="button" class="btn btn-link text-secondary p-0 position-absolute end-0 top-50 translate-middle-y me-3" data-bs-toggle="password" data-bs-target="confirmPassword" aria-label="Tampilkan/sembunyikan password">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                        <div class="invalid-feedback">Konfirmasi password tidak sama.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-key me-1"></i>Ubah password</button>
                </div>
            </form>
        </main>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>