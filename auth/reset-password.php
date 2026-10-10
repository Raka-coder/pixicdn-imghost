<?php
/**
 * ============================================================
 * File: auth/reset-password.php
 * Deskripsi: Set password baru dengan token sesi
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
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
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen flex items-center justify-center px-4" style="font-family:'Inter',system-ui,sans-serif">
<main class="w-full max-w-md bg-white border border-[#CBD2D6] rounded-xl p-6">
<p class="mb-4"><a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a></p>
<h1 class="font-semibold mb-4" style="font-size:24px">Password Baru</h1>
<?php if ($error !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($error) ?></p><?php endif; ?>
<form method="post" class="space-y-3">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<label class="block text-sm font-medium">Password baru (min 8)<input type="password" name="password" required minlength="8" class="w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]"></label>
<label class="block text-sm font-medium">Konfirmasi<input type="password" name="confirm" required class="w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]"></label>
<button class="w-full h-12 bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg">Simpan</button>
</form>
</main>
</body>
</html>
