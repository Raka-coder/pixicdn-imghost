<?php
/**
 * ============================================================
 * File: dashboard/album.php
 * Deskripsi: Detail album (tambah/hapus gambar, cover, hapus album) — Fase 3
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
$uid = (int) $user['id'];
$album = get_album((int) ($_GET['id'] ?? 0), $uid);
if ($album === null) {
    http_response_code(404);
    exit('Album tidak ditemukan');
}
$aid = (int) $album['id'];
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $err = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                $stmt = getDB()->prepare('SELECT id FROM images WHERE filename = ? AND user_id = ? AND is_deleted = FALSE');
                $stmt->execute([trim($_POST['filename'] ?? ''), $uid]);
                $img = $stmt->fetch();
                if (!$img) {
                    $err = 'Gambar tidak ditemukan atau bukan milik Anda.';
                } elseif (!add_to_album($aid, (int) $img['id'], $uid)) {
                    $err = 'Gagal menambah ke album.';
                } else {
                    $msg = 'Gambar ditambahkan.';
                }
            } elseif ($action === 'remove') {
                remove_from_album($aid, (int) ($_POST['image_id'] ?? 0), $uid);
                $msg = 'Gambar dikeluarkan.';
            } elseif ($action === 'cover') {
                $stmt = getDB()->prepare('SELECT id FROM images WHERE id = ? AND user_id = ? AND is_deleted = FALSE');
                $stmt->execute([(int) ($_POST['image_id'] ?? 0), $uid]);
                if ($stmt->fetch()) {
                    getDB()->prepare('UPDATE albums SET cover_image_id = ? WHERE id = ? AND user_id = ?')->execute([(int) $_POST['image_id'], $aid, $uid]);
                    $msg = 'Sampul diperbarui.';
                }
            } elseif ($action === 'delete') {
                getDB()->prepare('UPDATE albums SET is_deleted = TRUE WHERE id = ? AND user_id = ?')->execute([$aid, $uid]);
                redirect('/dashboard/albums.php?deleted=1');
            }
            $album = get_album($aid, $uid);
        } catch (PDOException $e) {
            error_log('Album detail gagal: ' . $e->getMessage());
            $err = 'Terjadi kesalahan pada sistem.';
        }
    }
}
$stmt = getDB()->prepare('SELECT i.* FROM images i JOIN album_images ai ON ai.image_id = i.id WHERE ai.album_id = ? AND i.is_deleted = FALSE ORDER BY ai.position, ai.added_at DESC');
$stmt->execute([$aid]);
$items = $stmt->fetchAll();
$share_url = rtrim(APP_URL, '/') . '/album.php?u=' . rawurlencode($user['username']) . '&s=' . rawurlencode($album['slug']);
$active = 'album';
$crumbs = [
    ['label' => 'Dashboard', 'url' => '/dashboard/'],
    ['label' => 'Album', 'url' => '/dashboard/albums.php'],
    ['label' => $album['name']],
];
$back_url = null; // HAPUS tombol kembali manual — breadcrumbs + sidebar sudah mewakili
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($album['name']) ?> — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen">
<?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="max-w-5xl w-full mx-auto px-6 py-10">
<?php require dirname(__DIR__) . '/includes/breadcrumbs.php'; ?>
<h1 class="page-title mb-1"><?= sanitize($album['name']) ?></h1>
<p class="text-[#687173] text-sm mb-4"><?= (int) $album['image_count'] ?> gambar · <?= $album['is_public'] ? 'publik' : 'privat' ?></p>
<div class="card mb-6 flex flex-wrap items-center gap-3">
<div class="flex-1 min-w-52">
<p class="text-sm font-medium mb-1">Link publik album</p>
<p class="mono text-xs break-all text-[#687173]"><?= sanitize($share_url) ?></p>
</div>
<button id="share-copy" class="btn btn-secondary btn-sm" data-url="<?= sanitize($share_url) ?>">Salin link</button>
</div>
<?php if ($msg !== ''): ?><p class="bg-[#E6F4EA] text-[#019C34] border border-[#019C34] rounded-lg p-3 mb-4 text-sm"><?= sanitize($msg) ?></p><?php endif; ?>
<?php if ($err !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($err) ?></p><?php endif; ?>
<form method="post" class="flex gap-2 mb-6">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="add">
<input name="filename" required placeholder="nama-file.jpg (milik Anda)" class="input flex-1" aria-label="Nama file gambar">
<button class="btn btn-primary">Tambah</button>
</form>
<div class="grid grid-cols-2 md:grid-cols-4 gap-6">
<?php foreach ($items as $img): ?>
<div class="bg-white border border-[#CBD2D6] rounded-xl overflow-hidden">
<img src="<?= sanitize($img['thumbnail_url'] ?: $img['public_url']) ?>" alt="<?= sanitize($img['filename']) ?>" loading="lazy" class="w-full h-32 object-cover">
<div class="p-2 flex gap-3 text-xs font-medium">
<form method="post"><input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>"><input type="hidden" name="action" value="cover"><input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>"><button class="text-[#003087]">Sampul</button></form>
<form method="post" onsubmit="return confirm('Keluarkan dari album?')"><input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="image_id" value="<?= (int) $img['id'] ?>"><button class="text-[#D20000]">Keluarkan</button></form>
</div>
</div>
<?php endforeach; ?>
</div>
<form method="post" class="mt-8" onsubmit="return confirm('Hapus album ini? (gambar tidak ikut terhapus)')">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="delete">
<button class="btn btn-danger">Hapus album</button>
</form>
<p class="mt-4 text-sm"><a href="/dashboard/albums.php" class="text-[#003087] underline">Semua album</a></p>
</main>
</div>
<footer class="md:ml-60 border-t border-[#CBD2D6] bg-white">
<div class="max-w-5xl mx-auto px-6 py-4 text-sm text-[#687173]">
<a href="/" class="hover:text-[#003087] font-medium">Halaman Utama</a>
</div>
</footer>
<script src="https://unpkg.com/lucide@latest"></script>
<script>
lucide.createIcons();
document.getElementById('share-copy').addEventListener('click', function () {
    navigator.clipboard.writeText(this.dataset.url);
    this.textContent = 'Tersalin!';
});
</script>
</body>
</html>
