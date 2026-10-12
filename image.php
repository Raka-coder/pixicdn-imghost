<?php
/**
 * ============================================================
 * File: image.php
 * Deskripsi: Direct link viewer + view counter (1 IP/jam)
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php'; // dev-fill saja, guarded defined()
require_once __DIR__ . '/includes/db.php';

$filename = basename($_GET['f'] ?? '');
if ($filename === '') {
    http_response_code(404);
    exit('Gambar tidak ditemukan');
}
try {
    $stmt = getDB()->prepare('SELECT * FROM images WHERE filename = ? AND is_deleted = FALSE');
    $stmt->execute([$filename]);
    $img = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Viewer gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi kesalahan pada sistem.');
}
if (!$img || ($img['expires_at'] !== null && strtotime((string) $img['expires_at']) < time())) {
    http_response_code(404);
    exit('Gambar tidak ditemukan');
}

// View counter: 1 IP = 1 view/jam
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
try {
    $stmt = getDB()->prepare('SELECT id FROM view_logs WHERE image_id = ? AND ip_address = ? AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1');
    $stmt->execute([(int) $img['id'], $ip]);
    if (!$stmt->fetch()) {
        getDB()->prepare('UPDATE images SET views = views + 1 WHERE id = ?')->execute([(int) $img['id']]);
        getDB()->prepare('INSERT INTO view_logs (image_id, ip_address, user_agent, referrer) VALUES (?, ?, ?, ?)')->execute([
            (int) $img['id'], $ip, mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000), mb_substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
        ]);
        $img['views'] = (int) $img['views'] + 1;
    }
} catch (PDOException $e) {
    error_log('View counter gagal: ' . $e->getMessage());
}

$url = (string) $img['public_url'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?> — PixiCDN</title>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="stylesheet" href="/assets/app.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Work Sans',system-ui,sans-serif">
<header class="bg-white border-b border-[#CBD2D6] h-16">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<a href="/gallery.php" class="hover:text-[#003087] font-medium">Gallery</a>
</nav>
</header>
<main class="max-w-3xl mx-auto px-6 py-10 text-center">
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6">
<img src="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-lg mb-4 border border-[#CBD2D6]">
<p class="text-[#687173] mb-4"><span class="tnum"><?= (int) $img['views'] ?> views</span> · <?= htmlspecialchars((string) ($img['original_name'] ?? $filename), ENT_QUOTES, 'UTF-8') ?></p>
<div class="grid gap-3 text-left">
<?php foreach (['Direct' => $url, 'BBCode' => '[img]' . $url . '[/img]', 'HTML' => '<img src="' . $url . '">'] as $label => $val): ?>
<label class="text-sm text-[#687173] font-medium"><?= $label ?>
<input readonly value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select();navigator.clipboard.writeText(this.value)" class="mono w-full mt-1 h-12 px-4 bg-white border border-[#CBD2D6] rounded-lg text-xs"></label>
<?php endforeach; ?>
</div>
</div>
<p class="mt-6"><a href="/gallery.php" class="text-[#003087] font-medium underline">Kembali ke gallery</a></p>
</main>
</body>
</html>
