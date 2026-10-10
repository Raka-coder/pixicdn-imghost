<?php
/**
 * ============================================================
 * File: dashboard/albums.php
 * Deskripsi: List + buat album (login) — Fase 3
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
require_once dirname(__DIR__) . '/includes/album.php';

require_login();
$user = current_user();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Token keamanan tidak valid.';
    } else {
        try {
            create_album((int) $user['id'], $_POST['name'] ?? '', $_POST['description'] ?? null ?: null);
            redirect('/dashboard/albums.php?ok=1');
        } catch (RuntimeException $e) {
            $err = $e->getMessage();
        } catch (PDOException $e) {
            error_log('Album buat gagal: ' . $e->getMessage());
            $err = 'Terjadi kesalahan pada sistem.';
        }
    }
}
$stmt = getDB()->prepare('SELECT * FROM albums WHERE user_id = ? AND is_deleted = FALSE ORDER BY created_at DESC');
$stmt->execute([(int) $user['id']]);
$albums = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Album — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Inter',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<a href="/dashboard/" class="hover:text-[#003087] font-medium">Dashboard</a>
</nav>
</header>
<main class="max-w-4xl mx-auto px-6 py-10">
<h1 class="font-bold mb-4" style="font-size:32px">Album Saya</h1>
<?php if ($err !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($err) ?></p><?php endif; ?>
<form method="post" class="bg-white border border-[#CBD2D6] rounded-xl p-6 mb-6 flex flex-wrap gap-2 items-end">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<label class="flex-1 min-w-40 text-sm font-medium">Nama<input name="name" required maxlength="100" class="w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]"></label>
<label class="flex-1 min-w-40 text-sm font-medium">Deskripsi<input name="description" maxlength="500" class="w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]"></label>
<button class="h-12 px-6 bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg">Buat</button>
</form>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<?php foreach ($albums as $a): ?>
<a href="/dashboard/album.php?id=<?= (int) $a['id'] ?>" class="bg-white border border-[#CBD2D6] rounded-xl p-6 block">
<p class="font-semibold"><?= sanitize($a['name']) ?></p>
<p class="text-sm text-[#687173]"><?= (int) $a['image_count'] ?> gambar · <?= $a['is_public'] ? 'publik' : 'privat' ?></p>
</a>
<?php endforeach; ?>
</div>
<?php if (!$albums): ?><p class="text-[#687173]">Belum ada album. Buat yang pertama di atas.</p><?php endif; ?>
<p class="mt-6 text-sm"><a href="/dashboard/" class="text-[#003087] underline">Dashboard</a></p>
</main>
</body>
</html>
