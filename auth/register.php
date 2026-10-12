<?php
/**
 * ============================================================
 * File: auth/register.php
 * Deskripsi: Registrasi user + tampilkan recovery code sekali
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/config.example.php'; // dev-fill saja, guarded defined()
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
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen flex items-center justify-center px-4" style="font-family:'Work Sans',system-ui,sans-serif">
<main class="w-full max-w-md bg-white border border-[#CBD2D6] rounded-xl p-6">
<p class="mb-4"><a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a></p>
<h1 class="font-semibold mb-4" style="font-size:24px">Daftar akun gratis</h1>
<?php if ($just_done): ?>
<div class="bg-[#FFF8E1] border border-[#F5BA2E] rounded-lg p-4 mb-4">
<p class="font-semibold mb-1">Simpan recovery code ini sekarang!</p>
<p class="tnum mono font-semibold" style="font-size:32px"><?= sanitize($recovery_code) ?></p>
<p class="text-sm text-[#687173] mt-1">Ditampilkan sekali saja. Dipakai untuk reset password.</p>
<a href="/dashboard/" class="btn btn-primary mt-3">Ke Dashboard</a>
</div>
<?php else: ?>
<?php if ($error !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($error) ?></p><?php endif; ?>
<form method="post" class="space-y-3">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<label class="block text-sm font-medium">Username<input name="username" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+" class="input mt-1"></label>
<label class="block text-sm font-medium">Password (min 8)<input type="password" name="password" required minlength="8" class="input mt-1"></label>
<label class="block text-sm font-medium">Konfirmasi<input type="password" name="confirm" required class="input mt-1"></label>
<button class="btn btn-primary w-full">Daftar</button>
</form>
<p class="text-sm text-[#687173] mt-3"><a href="/auth/login.php" class="text-[#003087] underline">Sudah punya akun? Masuk</a></p>
<?php endif; ?>
</main>
</body>
</html>
