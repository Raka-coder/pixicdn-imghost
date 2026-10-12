<?php
/**
 * ============================================================
 * File: includes/sidebar.php
 * Deskripsi: Sidebar navigasi dashboard dengan Bootstrap 5.3.
 *   Pakai di dalam offcanvas (mobile) dan sebagai sidebar fixed (desktop).
 *   Variabel yang dibutuhkan: $active = 'dashboard'|'upload'|'gallery'|'album'|'profile'|'api-key'
 * ============================================================
 */
declare(strict_types=1);

$active = $active ?? '';
$navMenu = [
    'dashboard' => ['label' => 'Dashboard', 'url' => '/dashboard/', 'icon' => 'speedometer2'],
    'upload' => ['label' => 'Upload', 'url' => '/upload.php', 'icon' => 'cloud-upload'],
    'gallery' => ['label' => 'Gallery', 'url' => '/gallery.php', 'icon' => 'images'],
    'album' => ['label' => 'Album', 'url' => '/dashboard/albums.php', 'icon' => 'folder'],
];
$navAkun = [
    'profile' => ['label' => 'Profil', 'url' => '/auth/profile.php', 'icon' => 'person'],
    'api-key' => ['label' => 'API Key', 'url' => '/dashboard/api-key.php', 'icon' => 'key'],
];
?>
<nav class="w-100" aria-label="Navigasi dashboard">
    <!-- Logo -->
    <a href="/dashboard/" class="d-flex align-items-center px-3 py-3 border-bottom border-2 border-primary fw-bold fs-5 text-primary text-decoration-none">
        <i class="bi bi-images me-2 fs-4"></i>PixiCDN
    </a>

    <!-- Menu Utama -->
    <div class="px-2 py-3">
        <p class="px-3 text-uppercase small fw-semibold text-secondary mb-2">Menu</p>
        <ul class="nav flex-column gap-1">
            <?php foreach ($navMenu as $key => $item): ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 text-body
                        <?= $active === $key ? ' active bg-primary-subtle text-primary fw-semibold' : ' text-secondary' ?>"
                       href="<?= $item['url'] ?>"
                       <?= $active === $key ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-<?= $item['icon'] ?> fs-5"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Akun -->
    <div class="px-2 pb-3">
        <p class="px-3 text-uppercase small fw-semibold text-secondary mb-2">Akun</p>
        <ul class="nav flex-column gap-1">
            <?php foreach ($navAkun as $key => $item): ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 text-body
                        <?= $active === $key ? ' active bg-primary-subtle text-primary fw-semibold' : ' text-secondary' ?>"
                       href="<?= $item['url'] ?>"
                       <?= $active === $key ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-<?= $item['icon'] ?> fs-5"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 text-danger"
                   href="/auth/logout.php">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                    <span>Keluar</span>
                </a>
            </li>
        </ul>
    </div>
</nav>