<?php
/**
 * ============================================================
 * File: includes/breadcrumbs.php
 * Deskripsi: Blok navigasi atas halaman internal — Back button (opsional)
 *   + breadcrumbs server-side (tanpa Alpine.js, repo tidak memakai Alpine).
 *
 *   Pakai sebelum <h1>:
 *     $crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'],
 *                ['label' => 'Upload']];          // item terakhir = halaman aktif
 *     $back_url = '/dashboard/';                  // opsional; null = tanpa tombol
 *     require __DIR__ . '/includes/breadcrumbs.php';
 *
 *   Aturan: halaman album TIDAK pakai $back_url (breadcrumbs + sidebar
 *   sudah cukup — dua mekanisme kembali = anti-pattern UX).
 * ============================================================
 */
declare(strict_types=1);

$crumbs = $crumbs ?? [];
$back_url = $back_url ?? null;
?>
<div class="flex flex-wrap items-center gap-2 mb-4">
<?php if ($back_url !== null): ?>
<a href="<?= sanitize($back_url) ?>" class="btn btn-secondary btn-sm transition-all duration-200">← Kembali</a>
<?php endif; ?>
<nav class="flex items-center gap-1 text-sm text-[#687173] min-w-0" aria-label="Breadcrumb">
<?php foreach ($crumbs as $i => $c): ?>
<?php if ($i > 0): ?>
<i data-lucide="chevron-right" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
<?php endif; ?>
<?php if (isset($c['url'])): ?>
<a href="<?= sanitize($c['url']) ?>" class="hover:text-[#003087] transition-all duration-200 whitespace-nowrap"><?= sanitize($c['label']) ?></a>
<?php else: ?>
<span class="font-medium text-[#1A1A2E] truncate max-w-[45vw] sm:max-w-xs" aria-current="page"><?= sanitize($c['label']) ?></span>
<?php endif; ?>
<?php endforeach; ?>
</nav>
</div>
