<?php
/**
 * ============================================================
 * File: dashboard/api-key.php
 * Deskripsi: Kelola API key (tampil sekali saja) — Tahap 4
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
require_once dirname(__DIR__) . '/includes/api.php';

require_login();
$user = current_user();
start_secure_session();
$plain = $_SESSION['api_key_plain'] ?? '';
unset($_SESSION['api_key_plain']);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $msg = 'Token keamanan tidak valid.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'generate' || $action === 'regenerate') {
                $_SESSION['api_key_plain'] = rotate_api_key((int) $user['id']);
                header('Location: /dashboard/api-key.php?done=1');
                exit;
            } elseif ($action === 'revoke') {
                revoke_api_key((int) $user['id']);
                $msg = 'API key dihapus.';
            }
        } catch (PDOException $e) {
            error_log('API key gagal: ' . $e->getMessage());
            $msg = 'Terjadi kesalahan pada sistem.';
        }
    }
}
$stmt = getDB()->prepare('SELECT api_key, api_key_created_at FROM users WHERE id = ?');
$stmt->execute([(int) $user['id']]);
$row = $stmt->fetch();
$has_key = !empty($row['api_key']);
$masked = $has_key ? '••••••••' . substr((string) $row['api_key'], -6) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Key — PixiCDN</title>
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
<a class="navlink" href="/dashboard/albums.php"><i data-lucide="folder"></i>Album</a>
<p class="px-3 pt-4 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Akun</p>
<a class="navlink" href="/auth/profile.php"><i data-lucide="user"></i>Profil</a>
<a class="navlink active" href="/dashboard/api-key.php" aria-current="page"><i data-lucide="key"></i>API key</a>
<a class="navlink" href="/auth/logout.php"><i data-lucide="log-out"></i>Keluar</a>
</nav>
</aside>
<div class="md:ml-60 min-h-screen flex flex-col">
<header class="md:hidden bg-white border-b border-[#CBD2D6] h-16 flex items-center gap-3 px-4 sticky top-0 z-20">
<button id="sb-open" class="p-2 rounded-lg hover:bg-[#F5F7FA]" aria-label="Buka menu"><i data-lucide="menu"></i></button>
<a href="/dashboard/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</header>
<main class="max-w-2xl w-full mx-auto px-6 py-10">
<nav class="flex items-center gap-1 text-sm text-[#687173] mb-4" aria-label="Breadcrumb">
<a href="/dashboard/" class="hover:text-[#003087]">Dashboard</a>
<i data-lucide="chevron-right" class="w-4 h-4"></i>
<span class="font-medium text-[#1A1A2E]" aria-current="page">API key</span>
</nav>
<div class="flex items-center gap-3 mb-4">
<a href="/dashboard/" class="btn btn-secondary btn-sm">← Kembali</a>
<h1 class="font-bold" style="font-size:32px">API Key</h1>
</div>
<p class="text-[#687173] text-sm mb-4">Pakai di header <code class="mono bg-white border border-[#CBD2D6] rounded px-2 py-1">X-API-Key</code>. Key hanya ditampilkan sekali setelah generate.</p>
<?php if ($plain !== ''): ?>
<div class="bg-[#FFF8E1] border border-[#F5BA2E] rounded-lg p-4 mb-4">
<p class="font-semibold">Salin sekarang, tidak bisa dilihat lagi:</p>
<p class="mono break-all mt-1"><?= sanitize($plain) ?></p>
</div>
<?php endif; ?>
<?php if ($msg !== ''): ?><p class="bg-white border border-[#CBD2D6] rounded-lg p-3 mb-4 text-sm"><?= sanitize($msg) ?></p><?php endif; ?>
<div class="card mb-4">
<p>Status: <?= $has_key ? 'aktif (' . sanitize($masked) . ')' : 'belum ada' ?></p>
<?php if ($has_key): ?><p class="text-sm text-[#687173]">Dibuat: <?= sanitize((string) ($row['api_key_created_at'] ?? '-')) ?></p><?php endif; ?>
</div>
<form method="post" class="flex gap-2">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="<?= $has_key ? 'regenerate' : 'generate' ?>">
<button class="btn btn-primary"><?= $has_key ? 'Regenerate' : 'Generate' ?></button>
</form>
<?php if ($has_key): ?>
<form method="post" class="mt-2" onsubmit="return confirm('Hapus API key?')">
<input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
<input type="hidden" name="action" value="revoke">
<button class="btn btn-danger">Hapus</button>
</form>
<?php endif; ?>
</main>
</div>
<footer class="md:ml-60 border-t border-[#CBD2D6] bg-white">
<div class="max-w-2xl mx-auto px-6 py-4 text-sm text-[#687173]">
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
