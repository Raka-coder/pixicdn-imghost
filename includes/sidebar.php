<?php
/**
 * ============================================================
 * File: includes/sidebar.php
 * Deskripsi: Shell navigasi dashboard — sidebar fixed + topbar mobile.
 *   Pakai sebelum <main>:
 *     $active = 'dashboard'|'upload'|'gallery'|'album'|'profile'|'api-key';
 *     require __DIR__ . '/includes/sidebar.php';
 *   Halaman WAJIB menutup wrapper: </main></div> lalu <footer> opsional.
 *   JS wiring sidebar ada di dalam include ini; halaman cukup memanggil
 *   lucide.createIcons() di akhir <body>.
 * ============================================================
 */
declare(strict_types=1);

$active = $active ?? '';
$navMenu = [
    'dashboard' => ['label' => 'Dashboard', 'url' => '/dashboard/', 'icon' => 'layout-dashboard'],
    'upload' => ['label' => 'Upload', 'url' => '/upload.php', 'icon' => 'upload'],
    'gallery' => ['label' => 'Gallery', 'url' => '/gallery.php', 'icon' => 'image'],
    'album' => ['label' => 'Album', 'url' => '/dashboard/albums.php', 'icon' => 'folder'],
];
$navAkun = [
    'profile' => ['label' => 'Profil', 'url' => '/auth/profile.php', 'icon' => 'user'],
    'api-key' => ['label' => 'API key', 'url' => '/dashboard/api-key.php', 'icon' => 'key'],
];
?>
<div id="sb-backdrop" class="fixed inset-0 bg-black/40 hidden md:hidden z-30"></div>
<aside id="sb" class="fixed top-0 left-0 h-full w-60 bg-white border-r border-[#CBD2D6] z-40 -translate-x-full md:translate-x-0 transition-transform flex flex-col" aria-label="Menu dashboard">
<div class="h-16 flex items-center px-6 border-b border-[#CBD2D6]">
<a href="/dashboard/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</div>
<nav class="p-4 space-y-1">
<p class="px-3 pt-2 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Menu</p>
<?php foreach ($navMenu as $key => $item): ?>
<a class="navlink<?= $active === $key ? ' active' : '' ?>" href="<?= $item['url'] ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><i data-lucide="<?= $item['icon'] ?>"></i><?= $item['label'] ?></a>
<?php endforeach; ?>
<p class="px-3 pt-4 pb-1 text-xs font-semibold text-[#687173] uppercase tracking-wide">Akun</p>
<?php foreach ($navAkun as $key => $item): ?>
<a class="navlink<?= $active === $key ? ' active' : '' ?>" href="<?= $item['url'] ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><i data-lucide="<?= $item['icon'] ?>"></i><?= $item['label'] ?></a>
<?php endforeach; ?>
<a class="navlink" href="/auth/logout.php"><i data-lucide="log-out"></i>Keluar</a>
</nav>
</aside>
<div class="md:ml-60 min-h-screen flex flex-col">
<header class="md:hidden bg-white border-b border-[#CBD2D6] h-16 flex items-center gap-3 px-4 sticky top-0 z-20">
<button id="sb-open" class="p-2 rounded-lg hover:bg-[#F5F7FA] transition-all duration-200" aria-label="Buka menu"><i data-lucide="menu"></i></button>
<a href="/dashboard/" class="text-xl font-bold text-[#003087]">PixiCDN</a>
</header>
<script>
(function () {
    const sb = document.getElementById('sb'), bd = document.getElementById('sb-backdrop');
    function setSb(open) { sb.classList.toggle('-translate-x-full', !open); bd.classList.toggle('hidden', !open); }
    document.getElementById('sb-open').addEventListener('click', () => setSb(true));
    bd.addEventListener('click', () => setSb(false));
    document.querySelectorAll('#sb a').forEach(a => a.addEventListener('click', () => setSb(false)));
})();
</script>
