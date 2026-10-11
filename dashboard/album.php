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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($album['name']) ?> — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Inter',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<a href="/dashboard/albums.php" class="hover:text-[#003087] font-medium">Semua album</a>
</nav>
</header>
<main class="max-w-5xl mx-auto px-6 py-10">
<h1 class="font-bold" style="font-size:32px"><?= sanitize($album['name']) ?></h1>
<p class="text-[#687173] text-sm mb-4"><?= (int) $album['image_count'] ?> gambar</p>
<?php if ($msg !== ''): ?><p class="bg-[#E6F4EA] text-[#019C34] border border-[#019C34] rounded-lg p-3 mb-4 text-sm"><?= sanitize($msg) ?></p><?php endif; ?>
<?php if ($err !== ''): ?><p class="bg-[#FDE7E7] text-[#D20000] border border-[#D20000] rounded-lg p-3 mb-4 text-sm" role="alert"><?= sanitize($err) ?></p><?php endif; ?>
<form method="post" class="flex gap-2 mb-6">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="add">
<input name="filename" required placeholder="nama-file.jpg (milik Anda)" class="flex-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg focus:outline-none focus:border-[#003087]" aria-label="Nama file gambar">
<button class="h-12 px-6 bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg">Tambah</button>
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
<button class="h-12 px-6 bg-[#D20000] text-white font-semibold rounded-lg">Hapus album</button>
</form>
<p class="mt-4 text-sm"><a href="/dashboard/albums.php" class="text-[#003087] underline">Semua album</a></p>
</main>
</body>
</html>
