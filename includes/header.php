<?php
/**
 * ============================================================
 * File: includes/header.php
 * Deskripsi: Header global dengan Bootstrap 5.3 CDN, custom theme variables,
 *   dan navbar mobile (offcanvas). Termasuk DOCTYPE, meta tags, fonts.
 *   Pakai di awal setiap halaman:
 *     require __DIR__ . '/includes/header.php';
 * ============================================================
 */
declare(strict_types=1);

$page_title = $page_title ?? 'PixiCDN';
$is_auth_page = $is_auth_page ?? false;
$is_public_page = $is_public_page ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($page_title) ?> — PixiCDN</title>
<meta name="description" content="PixiCDN: Hosting gambar gratis dengan direct link permanen untuk forum, blog, dan aplikasi. Gratis, tanpa iklan, dengan API.">

<!-- Bootstrap 5.3.3 CSS via jsDelivr -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- Google Fonts: Work Sans + JetBrains Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

<!-- Custom Theme Variables (Trust Blue) -->
<style>
:root {
    --bs-primary: #003087;
    --bs-primary-rgb: 0, 48, 135;
    --bs-success: #019c34;
    --bs-success-rgb: 1, 156, 52;
    --bs-info: #009cde;
    --bs-info-rgb: 0, 156, 222;
    --bs-warning: #f5ba2e;
    --bs-warning-rgb: 245, 186, 46;
    --bs-danger: #dc3545;
    --bs-danger-rgb: 220, 53, 69;
    --bs-primary-bg-subtle: #eaf0fa;
    --bs-primary-border-subtle: #b9c9e6;
    --bs-primary-text-emphasis: #00246b;
    --bs-success-bg-subtle: #e6f4ea;
    --bs-info-bg-subtle: #e6f5fc;
    --bs-warning-bg-subtle: #fff8e1;
    --bs-border-color: #cbd2d6;
    --bs-body-bg: #f5f7fa;
    --bs-body-color: #1a1a2e;
    --bs-font-sans-serif: 'Work Sans', system-ui, -apple-system, sans-serif;
    --bs-border-radius: 0.5rem;
    --bs-border-radius-lg: 0.75rem;
    --bs-link-color: #003087;
    --bs-link-hover-color: #00246b;
    --bs-focus-ring-color: rgba(0, 48, 135, 0.25);
}

/* Bootstrap mengompilasi warna tombol saat build — override var komponen */
.btn-primary {
    --bs-btn-bg: #003087;
    --bs-btn-border-color: #003087;
    --bs-btn-hover-bg: #00246b;
    --bs-btn-hover-border-color: #00246b;
    --bs-btn-active-bg: #00246b;
    --bs-btn-active-border-color: #00246b;
    --bs-btn-disabled-bg: #003087;
    --bs-btn-disabled-border-color: #003087;
}
.btn-outline-primary {
    --bs-btn-color: #003087;
    --bs-btn-border-color: #003087;
    --bs-btn-hover-bg: #003087;
    --bs-btn-hover-border-color: #003087;
    --bs-btn-active-bg: #003087;
    --bs-btn-active-border-color: #003087;
    --bs-btn-disabled-color: #003087;
    --bs-btn-disabled-border-color: #003087;
}
.page-item.active .page-link { background-color: #003087; border-color: #003087; }
.form-control:focus, .form-select:focus {
    border-color: #003087;
    box-shadow: 0 0 0 0.25rem rgba(0, 48, 135, 0.25);
}

/* Nav hover states */
.nav-landing:hover { color: var(--bs-primary) !important; }
aside .nav-link:not(.active):hover,
.offcanvas-body .nav-link:not(.active):hover { background-color: #f5f7fa; }

/* Custom button variants */
.btn-gold {
    --bs-btn-color: #1a1a2e;
    --bs-btn-bg: #f5ba2e;
    --bs-btn-border-color: #f5ba2e;
    --bs-btn-hover-color: #1a1a2e;
    --bs-btn-hover-bg: #e5a91e;
    --bs-btn-hover-border-color: #e5a91e;
    --bs-btn-active-color: #1a1a2e;
    --bs-btn-active-bg: #d4981c;
    --bs-btn-active-border-color: #c68a00;
}

/* Custom utility classes */
.mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
.tnum { font-feature-settings: "tnum"; }
.page-title { font-size: 2rem; font-weight: 700; line-height: 1.25; }
@media (max-width: 576px) { .page-title { font-size: 1.5rem; } }

/* Card shadow consistent with Trust Blue */
.card-trust {
    border: 1px solid var(--bs-border-color);
    border-radius: var(--bs-border-radius-lg);
    box-shadow: 0 1px 4px rgba(0, 48, 135, 0.06);
    background: #fff;
}
.card-trust:hover {
    box-shadow: 0 4px 16px rgba(0, 48, 135, 0.1);
}

/* Toast container positioning */
.toast-container {
    z-index: 1080;
}

/* Tooltip custom theme */
.tooltip-inner {
    background-color: var(--bs-primary);
    font-size: 0.875rem;
}
.tooltip.bs-tooltip-top .tooltip-arrow::before,
.tooltip.bs-tooltip-auto[data-popper-placement^="top"] .tooltip-arrow::before {
    border-top-color: var(--bs-primary);
}

/* Progress bar for password strength */
.progress-strength { height: 4px; border-radius: 2px; }

/* Ratio for gallery thumbnails */
.ratio-4x3 { aspect-ratio: 4 / 3; }

/* Max-height utility for modal preview */
.mh-70 { max-height: 70vh; }

/* Image zoom on hover (pengganti onmouseover inline) */
.zoom-hover { transform: scale(1); transition: transform 0.2s ease; }
.zoom-hover:hover { transform: scale(1.05); }

/* Drop zone upload */
.dropzone { cursor: pointer; transition: all 0.2s ease; }
.dropzone:hover, .dropzone.drag-active { border-color: var(--bs-primary); background-color: var(--bs-primary-bg-subtle); }

/* Fixed-size icon boxes (pengganti inline width/height) */
.icon-box { width: 48px; height: 48px; flex-shrink: 0; }
.icon-box-lg { width: 80px; height: 80px; flex-shrink: 0; }
.icon-circle { width: 64px; height: 64px; flex-shrink: 0; }

/* Section & auth titles (pengganti inline font-size) */
.section-title { font-size: 2rem; }
.auth-title { font-size: 1.5rem; }

/* Ritme vertikal landing: antar-section lega */
.section-gap { margin-bottom: 5rem; }
@media (max-width: 576px) { .section-gap { margin-bottom: 3rem; } }

/* Radius tombol seragam 8px di semua ukuran */
.btn, .btn-sm, .btn-lg { --bs-btn-border-radius: 0.5rem; }

/* Ikon landing proporsional konteks (lebih kecil dari dashboard) */
.icon-box-sm { width: 40px; height: 40px; flex-shrink: 0; }
.icon-num-sm { width: 56px; height: 56px; flex-shrink: 0; }

/* Desktop sidebar shell */
.sidebar-desktop { height: 100vh; z-index: 100; }

/* Auth card width */
.auth-card { max-width: 450px; }

/* Upload progress */
.progress-upload { height: 8px; }
#bar { width: 0%; transition: width 0.3s ease; }
.result-img { max-height: 300px; object-fit: contain; }

/* Password strength initial state */
#passwordStrength { width: 0%; }

/* Uppercase input (recovery code) */
.input-uppercase { text-transform: uppercase; }

/* Narrow content wrapper (pengganti max-w-3xl) */
.mw-3xl { max-width: 48rem; }

/* Preview thumbnail upload */
.thumb-preview { height: 96px; object-fit: cover; }

/* Nav spacer (mobile topbar balance) */
.nav-spacer { width: 42px; flex-shrink: 0; }

/* Click-to-copy boxes */
.copy-box { cursor: pointer; }

/* Transition for all interactive elements */
a, button, .btn, .form-control, .form-select, .nav-link, .card-trust {
    transition: all 0.2s ease;
}

/* Offcanvas sidebar width */
.offcanvas-sidebar {
    max-width: 280px;
    width: 100%;
}

/* Scrollbar styling */
::-webkit-scrollbar { width: 8px; height: 8px; }
::-webkit-scrollbar-track { background: var(--bs-body-bg); }
::-webkit-scrollbar-thumb { background: var(--bs-border-color); border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #adb5bd; }
</style>
</head>
<body class="min-vh-100 d-flex flex-column">

<?php if (!$is_auth_page && !$is_public_page): ?>
<!-- Mobile Offcanvas Sidebar (Dashboard) -->
<div class="offcanvas offcanvas-start offcanvas-sidebar" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel">
    <div class="offcanvas-header border-bottom border-2 border-primary">
        <h5 class="offcanvas-title fw-bold text-primary" id="sidebarOffcanvasLabel"><i class="bi bi-images me-2"></i>PixiCDN</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php require __DIR__ . '/sidebar.php'; ?>
    </div>
</div>

<!-- Top Navbar (Mobile) -->
<header class="bg-white border-bottom border-2 border-primary py-2 sticky-top d-md-none">
    <div class="container-fluid px-3 d-flex align-items-center justify-content-between">
        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Buka menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <a href="/dashboard/" class="fw-bold fs-5 text-primary text-decoration-none"><i class="bi bi-images me-1"></i>PixiCDN</a>
        <div class="nav-spacer"></div>
    </div>
</header>
<?php endif; ?>

<?php if ($is_public_page): ?>
<!-- Public Pages: Simple Header (mobile only — desktop pakai navbar halaman) -->
<header class="bg-white border-bottom border-2 border-primary py-2 d-md-none">
    <div class="container-fluid px-3 d-flex align-items-center justify-content-between">
        <a href="/" class="fw-bold fs-5 text-primary text-decoration-none"><i class="bi bi-images me-1"></i>PixiCDN</a>
        <a href="/gallery.php" class="text-secondary text-decoration-none fw-medium">Gallery</a>
    </div>
</header>
<?php endif; ?>