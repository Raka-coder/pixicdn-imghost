<?php
/**
 * ============================================================
 * File: index.php
 * Deskripsi: Landing page PixiCDN — Bootstrap 5.3 + Trust Blue
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}
require_once __DIR__ . '/config.example.php';

$stats = null;
try {
    require_once __DIR__ . '/includes/db.php';
    $images = (int) getDB()->query('SELECT COUNT(*) FROM images WHERE is_deleted = FALSE')->fetchColumn();
    $views = (int) getDB()->query('SELECT COALESCE(SUM(views),0) FROM images WHERE is_deleted = FALSE')->fetchColumn();
    $users = (int) getDB()->query('SELECT COUNT(*) FROM users WHERE is_active = TRUE')->fetchColumn();
    $stats = ['images' => $images, 'views' => $views, 'users' => $users];
} catch (Throwable $e) {
    $stats = null;
}

$page_title = 'PixiCDN — Hosting Gambar Gratis dengan Direct Link';
$is_public_page = true;
require __DIR__ . '/includes/header.php';
?>

<main class="flex-grow-1">
    <!-- Navbar Desktop -->
    <header class="bg-white border-bottom border-2 border-primary d-none d-md-block sticky-top">
        <nav class="container py-2 d-flex align-items-center justify-content-between" aria-label="Navigasi utama">
            <a href="/" class="fw-bold fs-4 text-primary text-decoration-none"><i class="bi bi-images me-1"></i>PixiCDN</a>
            <div class="d-flex align-items-center gap-4">
                <a href="#fitur" class="text-secondary text-decoration-none fw-medium small nav-landing">Fitur</a>
                <a href="#cara" class="text-secondary text-decoration-none fw-medium small nav-landing">Cara kerja</a>
                <a href="#api" class="text-secondary text-decoration-none fw-medium small nav-landing">API</a>
                <a href="/gallery.php" class="text-secondary text-decoration-none fw-medium small nav-landing">Gallery</a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="/auth/login.php" class="btn btn-outline-primary btn-sm">Masuk</a>
                <a href="/auth/register.php" class="btn btn-primary btn-sm">Upload gratis</a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-success-subtle text-success fw-medium px-3 py-2 mb-3">Gratis · Tanpa iklan · Wajib daftar</span>
                <h1 class="display-5 fw-bold text-body-emphasis lh-tight">Hosting gambar dengan direct link permanen</h1>
                <p class="lead text-secondary mt-3">Upload screenshot, foto, atau diagram — dapatkan link yang bisa di-embed di forum, blog, dan media sosial. Data tersimpan di server sendiri, bukan milik platform lain.</p>
                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="/auth/register.php" class="btn btn-primary btn-lg px-4">Upload sekarang</a>
                    <a href="/auth/register.php" class="btn btn-gold btn-lg px-4">Buat akun gratis</a>
                </div>
                <p class="text-secondary small mt-3">Wajib daftar gratis dulu — tanpa kartu kredit · JPG, PNG, GIF, WebP hingga 5 MB</p>
            </div>
            <div class="col-lg-6">
                <div class="card card-trust p-4 p-md-5 h-100">
                    <h3 class="fw-semibold mb-1">Setelah upload, Anda dapat</h3>
                    <p class="text-secondary small mb-4">Tiga format siap salin untuk setiap gambar.</p>
                    <div class="d-grid gap-3 mono small">
                        <div class="bg-body-tertiary border rounded-3 p-3 text-break copy-box" role="button" tabindex="0" data-copy-target="linkDirect" data-bs-toggle="tooltip" data-bs-title="Klik untuk salin">
                            https://pixicdn.erdtreeproject.my.id/abc123.jpg
                        </div>
                        <div class="bg-body-tertiary border rounded-3 p-3 text-break copy-box" role="button" tabindex="0" data-copy-target="linkBBCode" data-bs-toggle="tooltip" data-bs-title="Klik untuk salin">
                            [img]https://pixicdn.erdtreeproject.my.id/abc123.jpg[/img]
                        </div>
                        <div class="bg-body-tertiary border rounded-3 p-3 text-break copy-box" role="button" tabindex="0" data-copy-target="linkHTML" data-bs-toggle="tooltip" data-bs-title="Klik untuk salin">
                            &lt;img src="https://pixicdn.erdtreeproject.my.id/abc123.jpg"&gt;
                        </div>
                    </div>
                    <input type="hidden" id="linkDirect" value="https://pixicdn.erdtreeproject.my.id/abc123.jpg">
                    <input type="hidden" id="linkBBCode" value="[img]https://pixicdn.erdtreeproject.my.id/abc123.jpg[/img]">
                    <input type="hidden" id="linkHTML" value='<img src="https://pixicdn.erdtreeproject.my.id/abc123.jpg">'>
                    <a href="/auth/register.php" class="btn btn-primary mt-4 w-100">Coba langsung — daftar dulu</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Stats -->
    <?php if ($stats !== null): ?>
    <section class="container mb-5" aria-label="Statistik layanan">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-trust text-center p-4 h-100">
                    <p class="display-4 fw-bold tnum text-primary"><?= number_format($stats['images']) ?></p>
                    <p class="text-secondary small mb-0">Gambar tersimpan</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust text-center p-4 h-100">
                    <p class="display-4 fw-bold tnum text-info"><?= number_format($stats['views']) ?></p>
                    <p class="text-secondary small mb-0">Total views</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust text-center p-4 h-100">
                    <p class="display-4 fw-bold tnum text-warning"><?= number_format($stats['users']) ?></p>
                    <p class="text-secondary small mb-0">Pengguna terdaftar</p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features -->
    <section id="fitur" class="container mb-5">
        <div class="text-center mb-5">
            <h2 class="fw-semibold section-title">Semua yang Anda butuhkan, tanpa yang tidak</h2>
            <p class="text-secondary mt-2">Fokus pada hosting gambar yang cepat dan dapat diandalkan.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3 icon-box">
                        <i class="bi bi-link-45deg fs-4"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">Direct link permanen</h3>
                    <p class="text-secondary small mb-0">URL stabil untuk di-embed di mana saja. Tidak dihapus sepihak.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3 icon-box">
                        <i class="bi bi-filetype-bbcode fs-4"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">BBCode & HTML</h3>
                    <p class="text-secondary small mb-0">Setiap upload langsung disertai format forum dan blog, siap salin.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3 icon-box">
                        <i class="bi bi-collection fs-4"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">Gallery + Album</h3>
                    <p class="text-secondary small mb-0">Kelola koleksi dengan filter, pencarian, dan album yang bisa dibagikan.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3 icon-box">
                        <i class="bi bi-code-slash fs-4"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">REST API</h3>
                    <p class="text-secondary small mb-0">Upload terprogram via API key untuk integrasi developer.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3 icon-box">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">Expiry Otomatis</h3>
                    <p class="text-secondary small mb-0">Atur gambar kedaluwarsa 1 jam hingga 30 hari, atau selamanya.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3 icon-box">
                        <i class="bi bi-lightning-charge fs-4"></i>
                    </div>
                    <h3 class="fw-semibold mb-2">CDN Cepat</h3>
                    <p class="text-secondary small mb-0">Disajikan via edge global dengan thumbnail ringan 300px.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="cara" class="container mb-5">
        <div class="text-center mb-5">
            <h2 class="fw-semibold section-title">Dari upload ke embed dalam 3 langkah</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle text-info rounded-circle mb-3 icon-circle">
                        <span class="fw-bold tnum fs-1">1</span>
                    </div>
                    <h3 class="fw-semibold mb-2">Upload</h3>
                    <p class="text-secondary small mb-0">Tarik & letakkan hingga 5 gambar, tanpa atau dengan akun.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle text-info rounded-circle mb-3 icon-circle">
                        <span class="fw-bold tnum fs-1">2</span>
                    </div>
                    <h3 class="fw-semibold mb-2">Salin Link</h3>
                    <p class="text-secondary small mb-0">Pilih Direct, BBCode, atau HTML — satu klik tersalin.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center bg-info-subtle text-info rounded-circle mb-3 icon-circle">
                        <span class="fw-bold tnum fs-1">3</span>
                    </div>
                    <h3 class="fw-semibold mb-2">Kelola</h3>
                    <p class="text-secondary small mb-0">Pantau views di dashboard, atur ke album, hapus kapan saja.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Personas -->
    <section class="container mb-5">
        <div class="text-center mb-5">
            <h2 class="fw-semibold section-title">Dibuat untuk cara Anda bekerja</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <p class="d-flex align-items-center gap-2 fw-semibold mb-2">
                        <span class="badge rounded-pill bg-success px-3 py-2"><i class="bi bi-chat-square-text me-1"></i>Forum & Komunitas</span>
                    </p>
                    <p class="text-secondary small mb-0">Screenshot bukti dan diskusi dengan BBCode siap tempel.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <p class="d-flex align-items-center gap-2 fw-semibold mb-2">
                        <span class="badge rounded-pill bg-warning text-dark px-3 py-2"><i class="bi bi-pencil-square me-1"></i>Blogger & Kreator</span>
                    </p>
                    <p class="text-secondary small mb-0">Galeri ringan untuk ilustrasi artikel tanpa plugin berat.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-trust p-4 h-100">
                    <p class="d-flex align-items-center gap-2 fw-semibold mb-2">
                        <span class="badge rounded-pill bg-info px-3 py-2"><i class="bi bi-code me-1"></i>Developer</span>
                    </p>
                    <p class="text-secondary small mb-0">API key + endpoint JSON untuk pipeline upload otomatis.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- API Teaser -->
    <section id="api" class="container mb-5">
        <div class="card card-trust p-4 p-md-5">
            <h2 class="fw-semibold mb-3 auth-title">Integrasi dalam hitungan menit</h2>
            <p class="text-secondary mb-4">Satu header <span class="mono small bg-body-tertiary border rounded px-2 py-1">X-API-Key</span> untuk semua endpoint. Ambil key di dashboard setelah daftar.</p>
            <pre class="mono small bg-dark text-light rounded-3 p-4 overflow-auto"><code>curl -H "X-API-Key: <key>" \
  -F image=@foto.jpg \
  https://pixicdn.erdtreeproject.my.id/api/upload.php</code></pre>
            <p class="mt-4 mb-0"><a href="/auth/register.php" class="fw-semibold link-primary link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover">Dapatkan API key gratis →</a></p>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="container mb-5">
        <div class="bg-primary rounded-4 p-4 p-md-5 text-center text-white">
            <h2 class="fw-bold section-title">Mulai hosting gambar Anda hari ini</h2>
            <p class="mt-2 text-white text-opacity-75">Gratis, tanpa kartu kredit, tanpa iklan.</p>
            <div class="mt-4 d-flex flex-wrap justify-content-center gap-3">
                <a href="/auth/register.php" class="btn btn-gold btn-lg px-4">Upload pertama</a>
                <a href="/gallery.php" class="btn btn-outline-light btn-lg px-4">Lihat gallery</a>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>