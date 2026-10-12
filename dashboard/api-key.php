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
$active = 'api-key';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'API key']];
$back_url = null;
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
<?php require dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="max-w-2xl w-full mx-auto px-6 py-10">
<?php require dirname(__DIR__) . '/includes/breadcrumbs.php'; ?>
<h1 class="page-title mb-4">API Key</h1>
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
</script>
</body>
</html>
