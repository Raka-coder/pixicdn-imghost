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
$active = 'album';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'Album']];
$back_url = null; // breadcrumbs + sidebar sudah cukup (tanpa tombol kembali ganda)
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
<?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="max-w-4xl w-full mx-auto px-6 py-10">
<?php require dirname(__DIR__) . '/includes/breadcrumbs.php'; ?>
<h1 class="page-title mb-4">Album Saya</h1>
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
</script>
</body>
</html>
