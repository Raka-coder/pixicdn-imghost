<?php
/**
 * ============================================================
 * File: index.php
 * Deskripsi: Landing page PixiCDN — Trust Blue design system
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.example.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

$stats = null;
try {
    require_once __DIR__ . '/includes/db.php';
    $images = (int) getDB()->query('SELECT COUNT(*) FROM images WHERE is_deleted = FALSE')->fetchColumn();
    $views = (int) getDB()->query('SELECT COALESCE(SUM(views),0) FROM images WHERE is_deleted = FALSE')->fetchColumn();
    $users = (int) getDB()->query('SELECT COUNT(*) FROM users WHERE is_active = TRUE')->fetchColumn();
    $stats = ['images' => $images, 'views' => $views, 'users' => $users];
} catch (Throwable $e) {
    $stats = null; // DB belum dikonfigurasi — sembunyikan strip angka
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PixiCDN — Hosting Gambar Gratis dengan Direct Link</title>
<meta name="description" content="PixiCDN: upload gambar, dapatkan direct link permanen untuk forum, blog, dan aplikasi. Gratis, tanpa iklan, dengan API.">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', system-ui, sans-serif; }
.tnum { font-feature-settings: "tnum"; }
.card-shadow { box-shadow: 0 1px 4px rgba(0,48,135,0.06); }
.card-shadow:hover { box-shadow: 0 4px 16px rgba(0,48,135,0.1); }
</style>
</head>
<body class="bg-[#F5F7FA] text-[#1A1A2E] min-h-screen" style="font-family:'Inter',system-ui,sans-serif">

<!-- Navigasi: bar putih 64px -->
<header class="bg-white border-b border-[#CBD2D6] h-16 sticky top-0 z-10">
<nav class="max-w-[1120px] mx-auto px-6 h-16 flex items-center justify-between" aria-label="Navigasi utama">
<a href="/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
<div class="hidden md:flex items-center gap-8 text-[15px] font-medium">
<a href="#fitur" class="hover:text-[#003087] hover:underline hover:decoration-2 hover:underline-offset-4">Fitur</a>
<a href="#cara" class="hover:text-[#003087] hover:decoration-2 hover:underline hover:underline-offset-4">Cara kerja</a>
<a href="#api" class="hover:text-[#003087] hover:decoration-2 hover:underline hover:underline-offset-4">API</a>
<a href="/gallery.php" class="hover:text-[#003087] hover:decoration-2 hover:underline hover:underline-offset-4">Gallery</a>
</div>
<div class="flex items-center gap-3">
<a href="/auth/login.php" class="hidden sm:inline-block h-12 px-6 leading-[48px] text-[#003087] font-semibold rounded-lg border-2 border-[#003087]">Masuk</a>
<a href="/auth/register.php" class="h-12 px-6 leading-[48px] bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg min-w-[120px] text-center">Upload gratis</a>
</div>
</nav>
</header>

<main class="max-w-[1120px] mx-auto px-6">

<!-- Hero -->
<section class="py-16 md:py-24 grid md:grid-cols-2 gap-12 items-center">
<div>
<span class="inline-block h-8 px-3 leading-8 rounded-full bg-[#E6F4EA] text-[#019C34] text-sm font-medium mb-4">Gratis · Tanpa iklan · Wajib daftar</span>
<h1 class="font-bold tracking-tight" style="font-size:40px;line-height:1.25;letter-spacing:-0.02em">Hosting gambar dengan direct link permanen</h1>
<p class="mt-4 text-lg text-[#687173]" style="line-height:1.5">Upload screenshot, foto, atau diagram — dapatkan link yang bisa di-embed di forum, blog, dan media sosial. Data tersimpan di server sendiri, bukan milik platform lain.</p>
<div class="mt-8 flex flex-wrap gap-4">
<a href="/auth/register.php" class="h-12 px-6 leading-[48px] bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg min-w-[120px] text-center">Upload sekarang</a>
<a href="/auth/register.php" class="h-12 px-6 leading-[48px] bg-[#F5BA2E] text-[#1A1A2E] font-semibold rounded-lg min-w-[120px] text-center">Buat akun gratis</a>
</div>
<p class="mt-4 text-sm text-[#687173]">Wajib daftar gratis dulu — tanpa kartu kredit · JPG, PNG, GIF, WebP hingga 5 MB</p>
</div>
<!-- Demo mini: kartu format link -->
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow">
<h2 class="text-lg font-semibold mb-1">Setelah upload, Anda dapat</h2>
<p class="text-sm text-[#687173] mb-4">Tiga format siap salin untuk setiap gambar.</p>
<div class="space-y-3 font-mono text-sm">
<div class="bg-[#F5F7FA] border border-[#CBD2D6] rounded-lg p-3 break-all">https://pixicdn.erdtreeproject.my.id/abc123.jpg</div>
<div class="bg-[#F5F7FA] border border-[#CBD2D6] rounded-lg p-3 break-all">[img]https://pixicdn.erdtreeproject.my.id/abc123.jpg[/img]</div>
<div class="bg-[#F5F7FA] border border-[#CBD2D6] rounded-lg p-3 break-all">&lt;img src="https://pixicdn.erdtreeproject.my.id/abc123.jpg"&gt;</div>
</div>
<a href="/auth/register.php" class="mt-4 inline-block h-12 px-6 leading-[48px] bg-[#003087] hover:bg-[#00246B] text-white font-semibold rounded-lg">Coba langsung — daftar dulu</a>
</div>
</section>

<?php if ($stats !== null): ?>
<!-- Strip statistik trust (angka nyata dari database) -->
<section class="grid grid-cols-3 gap-6 mb-16" aria-label="Statistik layanan">
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 text-center card-shadow">
<p class="tnum font-bold" style="font-size:32px"><?= number_format($stats['images']) ?></p>
<p class="text-[#687173] text-sm">Gambar tersimpan</p>
</div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 text-center card-shadow">
<p class="tnum font-bold" style="font-size:32px"><?= number_format($stats['views']) ?></p>
<p class="text-[#687173] text-sm">Total views</p>
</div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 text-center card-shadow">
<p class="tnum font-bold" style="font-size:32px"><?= number_format($stats['users']) ?></p>
<p class="text-[#687173] text-sm">Pengguna terdaftar</p>
</div>
</section>
<?php endif; ?>

<!-- Fitur -->
<section id="fitur" class="mb-16">
<h2 class="font-semibold text-center" style="font-size:32px">Semua yang Anda butuhkan, tanpa yang tidak</h2>
<p class="text-center text-[#687173] mt-2 mb-8">Fokus pada hosting gambar yang cepat dan dapat diandalkan.</p>
<div class="grid md:grid-cols-3 gap-6">
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold text-lg mb-2">Direct link permanen</h3><p class="text-[#687173] text-sm">URL stabil untuk di-embed di mana saja. Tidak dihapus sepihak.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold text-lg mb-2">BBCode &amp; HTML</h3><p class="text-[#687173] text-sm">Setiap upload langsung disertai format forum dan blog, siap salin.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold text-lg mb-2">Gallery + album</h3><p class="text-[#687173] text-sm">Kelola koleksi dengan filter, pencarian, dan album yang bisa dibagikan.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold text-lg mb-2">REST API</h3><p class="text-[#687173] text-sm">Upload terprogram via API key untuk integrasi developer.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold text-lg mb-2">Expiry otomatis</h3><p class="text-[#687173] text-sm">Atur gambar kedaluwarsa 1 jam hingga 30 hari, atau selamanya.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold text-lg mb-2">CDN cepat</h3><p class="text-[#687173] text-sm">Disajikan via edge global dengan thumbnail ringan 300px.</p></div>
</div>
</section>

<!-- Cara kerja -->
<section id="cara" class="mb-16">
<h2 class="font-semibold text-center" style="font-size:32px">Dari upload ke embed dalam 3 langkah</h2>
<div class="grid md:grid-cols-3 gap-6 mt-8">
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><p class="tnum font-bold text-[#009CDE]" style="font-size:32px">1</p><h3 class="font-semibold mt-2">Upload</h3><p class="text-[#687173] text-sm">Tarik &amp; letakkan hingga 5 gambar, tanpa atau dengan akun.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><p class="tnum font-bold text-[#009CDE]" style="font-size:32px">2</p><h3 class="font-semibold mt-2">Salin link</h3><p class="text-[#687173] text-sm">Pilih Direct, BBCode, atau HTML — satu klik tersalin.</p></div>
<div class="bg-white border border-[#CBD2D6] rounded-xl p-6 card-shadow"><p class="tnum font-bold text-[#009CDE]" style="font-size:32px">3</p><h3 class="font-semibold mt-2">Kelola</h3><p class="text-[#687173] text-sm">Pantau views di dashboard, atur ke album, hapus kapan saja.</p></div>
</div>
</section>

<!-- Cocok untuk (persona PRD) -->
<section class="mb-16">
<h2 class="font-semibold text-center" style="font-size:32px">Dibuat untuk cara Anda bekerja</h2>
<div class="grid md:grid-cols-3 gap-6 mt-8">
<div class="bg-white border-l-4 border-l-[#019C34] border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold">Forum &amp; komunitas</h3><p class="text-[#687173] text-sm mt-1">Screenshot bukti dan diskusi dengan BBCode siap tempel.</p></div>
<div class="bg-white border-l-4 border-l-[#F5BA2E] border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold">Blogger &amp; kreator</h3><p class="text-[#687173] text-sm mt-1">Galeri ringan untuk ilustrasi artikel tanpa plugin berat.</p></div>
<div class="bg-white border-l-4 border-l-[#009CDE] border border-[#CBD2D6] rounded-xl p-6 card-shadow"><h3 class="font-semibold">Developer</h3><p class="text-[#687173] text-sm mt-1">API key + endpoint JSON untuk pipeline upload otomatis.</p></div>
</div>
</section>

<!-- API teaser -->
<section id="api" class="mb-16 bg-white border border-[#CBD2D6] rounded-xl p-6 md:p-8 card-shadow">
<h2 class="font-semibold" style="font-size:24px">Integrasi dalam hitungan menit</h2>
<p class="text-[#687173] mt-2 mb-4">Satu header <span class="font-mono text-sm bg-[#F5F7FA] border border-[#CBD2D6] rounded px-2 py-1">X-API-Key</span> untuk semua endpoint. Ambil key di dashboard setelah daftar.</p>
<pre class="font-mono text-sm bg-[#1A1A2E] text-white rounded-lg p-4 overflow-x-auto" style="font-family:'JetBrains Mono',monospace">curl -H "X-API-Key: &lt;key&gt;" \
  -F image=@foto.jpg \
  https://pixicdn.erdtreeproject.my.id/api/upload.php</pre>
<p class="mt-4"><a href="/auth/register.php" class="text-[#003087] font-semibold hover:underline">Dapatkan API key gratis →</a></p>
</section>

<!-- CTA final -->
<section class="mb-16 bg-[#003087] rounded-2xl p-8 md:p-12 text-center text-white">
<h2 class="font-bold" style="font-size:32px">Mulai hosting gambar Anda hari ini</h2>
<p class="mt-2 text-white/80">Gratis, tanpa kartu kredit, tanpa iklan.</p>
<div class="mt-6 flex flex-wrap justify-center gap-4">
<a href="/auth/register.php" class="h-12 px-6 leading-[48px] bg-[#F5BA2E] text-[#1A1A2E] font-semibold rounded-lg min-w-[120px]">Upload pertama</a>
<a href="/gallery.php" class="h-12 px-6 leading-[48px] bg-white text-[#003087] font-semibold rounded-lg min-w-[120px]">Lihat gallery</a>
</div>
</section>

</main>

<footer class="bg-white border-t border-[#CBD2D6]">
<div class="max-w-[1120px] mx-auto px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-[#687173]">
<p><span class="font-bold text-[#003087]">PixiCDN</span> — self-hosted image hosting.</p>
<div class="flex gap-6">
<a href="/auth/register.php" class="hover:text-[#003087]">Upload</a>
<a href="/gallery.php" class="hover:text-[#003087]">Gallery</a>
<a href="/auth/login.php" class="hover:text-[#003087]">Masuk</a>
</div>
</div>
</footer>

</body>
</html>
