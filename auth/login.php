<?php
/**
 * ============================================================
 * File: auth/login.php
 * Deskripsi: Login user dengan session regenerate
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
require_once dirname(__DIR__) . '/includes/upload.php'; // check_rate_limit

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
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen flex items-center justify-center px-4" style="font-family:'Inter',system-ui,sans-serif">
<main class="w-full max-w-md bg-white border border-[#CBD2D6] rounded-xl p-6">
<p class="mb-4"><a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a></p>
<h1 class="font-semibold mb-4" style="font-size:24px">Selamat datang kembali</h1>
<?php if ($error !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($error) ?></p><?php endif; ?>
<form method="post" class="space-y-3">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<label class="block text-sm font-medium">Username<input name="username" required class="w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]"></label>
<label class="block text-sm font-medium">Password<input type="password" name="password" required class="w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]"></label>
<button class="w-full h-12 bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg">Masuk</button>
</form>
<p class="text-sm text-[#687173] mt-3"><a href="/auth/forgot-password.php" class="text-[#003087] underline">Lupa password?</a> · <a href="/auth/register.php" class="text-[#003087] underline">Daftar</a></p>
</main>
</body>
</html>
