<?php
/**
 * ============================================================
 * File: auth/profile.php
 * Deskripsi: Info user + edit username + ubah password (Tahap 4)
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

require_login();
$user = current_user();
$msg = '';
$err = '';

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
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Work Sans',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<a href="/dashboard/" class="hover:text-[#003087] font-medium">Dashboard</a>
</nav>
</header>
<main class="max-w-2xl mx-auto px-6 py-10">
<h1 class="font-bold mb-4" style="font-size:32px">Profil</h1>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 mb-4 text-sm">
<p>Username: <b><?= sanitize($user['username']) ?></b></p>
<p class="text-[#687173]">Bergabung: <?= sanitize((string) $user['created_at']) ?></p>
<p class="text-[#687173]">Storage: <span class="tnum"><?= round(((int) $user['storage_used']) / 1048576, 2) ?> / <?= round(((int) $user['storage_quota']) / 1048576) ?> MB</span></p>
</div>
<?php if ($msg !== ''): ?><p class="bg-[#E6F4EA] text-[#019C34] border border-[#019C34] rounded-lg p-3 mb-4 text-sm"><?= sanitize($msg) ?></p><?php endif; ?>
<?php if ($err !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($err) ?></p><?php endif; ?>
<form method="post" class="bg-white border border-[#CBD2D6] rounded-xl p-6 mb-4 space-y-3">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="username">
<label class="block text-sm font-medium">Username baru<input name="username" required pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="30" value="<?= sanitize($user['username']) ?>" class="input mt-1"></label>
<button class="btn btn-primary">Simpan username</button>
</form>
<form method="post" class="bg-white border border-[#CBD2D6] rounded-xl p-6 space-y-3">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="password">
<label class="block text-sm font-medium">Password lama<input type="password" name="old" required class="input mt-1"></label>
<label class="block text-sm font-medium">Password baru (min 8)<input type="password" name="new" required minlength="8" class="input mt-1"></label>
<label class="block text-sm font-medium">Konfirmasi<input type="password" name="confirm" required class="input mt-1"></label>
<button class="btn btn-primary">Ubah password</button>
</form>
<p class="mt-4 text-sm"><a href="/dashboard/" class="text-[#003087] underline">Dashboard</a> · <a href="/dashboard/api-key.php" class="text-[#003087] underline">API key</a></p>
</main>
</body>
</html>
