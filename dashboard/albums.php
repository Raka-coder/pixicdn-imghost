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
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/config.example.php'; // dev-fill saja, guarded defined()
require_once dirname(__DIR__) . '/includes/helpers.php';
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
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen">
<div id="sb-backdrop" class="fixed inset-0 bg-black/40 hidden md:hidden z-30"></div>
<aside id="sb" class="fixed top-0 left-0 h-full w-60 bg-white border-r border-[#CBD2D6] z-40 -translate-x-full md:translate-x-0 transition-transform flex flex-col" aria-label="Menu dashboard">
<div class="h-16 flex items-center px-6 border-b border-[#CBD2D6]">
<a href="/dashboard/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</div>
<nav class="p-4 space-y-1">
<p class="px-3 pt-2 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Menu</p>
<a class="navlink" href="/dashboard/"><i data-lucide="layout-dashboard"></i>Dashboard</a>
<a class="navlink" href="/upload.php"><i data-lucide="upload"></i>Upload</a>
<a class="navlink" href="/gallery.php"><i data-lucide="image"></i>Gallery</a>
<a class="navlink active" href="/dashboard/albums.php" aria-current="page"><i data-lucide="folder"></i>Album</a>
<p class="px-3 pt-4 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Akun</p>
<a class="navlink" href="/auth/profile.php"><i data-lucide="user"></i>Profil</a>
<a class="navlink" href="/dashboard/api-key.php"><i data-lucide="key"></i>API key</a>
<a class="navlink" href="/auth/logout.php"><i data-lucide="log-out"></i>Keluar</a>
</nav>
</aside>
<div class="md:ml-60 min-h-screen flex flex-col">
<header class="md:hidden bg-white border-b border-[#CBD2D6] h-16 flex items-center gap-3 px-4 sticky top-0 z-20">
<button id="sb-open" class="p-2 rounded-lg hover:bg-[#F5F7FA]" aria-label="Buka menu"><i data-lucide="menu"></i></button>
<a href="/dashboard/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</header>
<main class="max-w-4xl w-full mx-auto px-6 py-10">
<nav class="flex items-center gap-1 text-sm text-[#687173] mb-4" aria-label="Breadcrumb">
<a href="/dashboard/" class="hover:text-[#003087]">Dashboard</a>
<i data-lucide="chevron-right" class="w-4 h-4"></i>
<span class="font-medium text-[#1A1A2E]" aria-current="page">Album</span>
</nav>
<div class="flex items-center gap-3 mb-4">
<a href="/dashboard/" class="btn btn-secondary btn-sm">← Kembali</a>
<h1 class="font-bold" style="font-size:32px">Album Saya</h1>
</div>
<?php if ($err !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($err) ?></p><?php endif; ?>
<form method="post" class="card mb-6 flex flex-wrap gap-2 items-end">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<label class="flex-1 min-w-40 text-sm font-medium">Nama<input name="name" required maxlength="100" class="input mt-1"></label>
<label class="flex-1 min-w-40 text-sm font-medium">Deskripsi<input name="description" maxlength="500" class="input mt-1"></label>
<button class="btn btn-primary">Buat</button>
</form>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<?php foreach ($albums as $a): ?>
<a href="/dashboard/album.php?id=<?= (int) $a['id'] ?>" class="card block">
<p class="font-semibold"><?= sanitize($a['name']) ?></p>
<p class="text-sm text-[#687173]"><?= (int) $a['image_count'] ?> gambar · <?= $a['is_public'] ? 'publik' : 'privat' ?></p>
</a>
<?php endforeach; ?>
</div>
<?php if (!$albums): ?><p class="text-[#687173]">Belum ada album. Buat yang pertama di atas.</p><?php endif; ?>
</main>
</div>
<footer class="md:ml-60 border-t border-[#CBD2D6] bg-white">
<div class="max-w-4xl mx-auto px-6 py-4 text-sm text-[#687173]">
<a href="/" class="hover:text-[#003087] font-medium">Halaman Utama</a>
</div>
</footer>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
const sb = document.getElementById('sb'), bd = document.getElementById('sb-backdrop');
function setSb(open) { sb.classList.toggle('-translate-x-full', !open); bd.classList.toggle('hidden', !open); }
document.getElementById('sb-open').addEventListener('click', () => setSb(true));
bd.addEventListener('click', () => setSb(false));
document.querySelectorAll('#sb a').forEach(a => a.addEventListener('click', () => setSb(false)));
</script>
</body>
</html>
