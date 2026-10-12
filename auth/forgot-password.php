<?php
/**
 * ============================================================
 * File: auth/forgot-password.php
 * Deskripsi: Request reset via recovery code (no enumeration)
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
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $message = 'Token keamanan tidak valid.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        request_password_reset(trim($_POST['username'] ?? ''), trim($_POST['recovery_code'] ?? ''), $ip);
        // Pesan sama apapun hasilnya agar tidak bocor enumerasi user
        $message = 'Jika data cocok, Anda bisa lanjut set password baru.';
        if (isset($_SESSION['reset_token_plain'])) {
            header('Location: /auth/reset-password.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lupa Password — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen flex items-center justify-center px-4" style="font-family:'Work Sans',system-ui,sans-serif">
<main class="w-full max-w-md bg-white border border-[#CBD2D6] rounded-xl p-6">
<p class="mb-4"><a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a></p>
<h1 class="font-semibold mb-4" style="font-size:24px">Lupa Password</h1>
<?php if ($message !== ''): ?><p class="bg-[#F5F7FA] border border-[#CBD2D6] rounded-lg p-3 mb-4 text-sm"><?= sanitize($message) ?></p><?php endif; ?>
<form method="post" class="space-y-3">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<label class="block text-sm font-medium">Username<input name="username" required class="input mt-1"></label>
<label class="block text-sm font-medium">Recovery code (8 karakter)<input name="recovery_code" required minlength="8" maxlength="8" class="input mono mt-1"></label>
<button class="btn btn-primary w-full">Lanjut</button>
</form>
</main>
</body>
</html>
