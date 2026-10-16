<?php
/**
 * ============================================================
 * File: upload.php
 * Deskripsi: UI upload drag-drop native + preview + progress + Toast — Bootstrap 5.3
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
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

require_login();
start_secure_session();
$token = csrfToken();
$active = 'upload';
$crumbs = [['label' => 'Dashboard', 'url' => '/dashboard/'], ['label' => 'Upload']];
$back_url = '/dashboard/';
?>
<?php
$page_title = 'Upload — PixiCDN';
require __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row g-0">
        <!-- Sidebar Desktop -->
        <aside class="col-md-3 col-lg-2 d-none d-md-block bg-white border-end border-2 border-primary min-vh-100 position-sticky top-0 sidebar-desktop">
            <?php require __DIR__ . '/includes/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-5">
            <!-- Breadcrumb + Back Button -->
            <nav aria-label="Breadcrumb" class="mb-4">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <?php if ($back_url !== null): ?>
                        <a href="<?= sanitize($back_url) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                    <?php endif; ?>
                    <ol class="breadcrumb mb-0 flex-wrap">
                        <?php foreach ($crumbs as $i => $c): ?>
                            <?php if ($i > 0): ?>
                                <li class="breadcrumb-item"><i class="bi bi-chevron-right text-secondary"></i></li>
                            <?php endif; ?>
                            <?php if (isset($c['url'])): ?>
                                <li class="breadcrumb-item"><a href="<?= sanitize($c['url']) ?>" class="text-decoration-none text-secondary"><?= sanitize($c['label']) ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active fw-semibold text-body-emphasis" aria-current="page"><?= sanitize($c['label']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </nav>

            <h1 class="page-title mb-1">Upload Gambar</h1>
            <p class="text-secondary mb-4">JPG, PNG, GIF, WebP · maks 5 MB/file · maks 5 file</p>

            <!-- Drop Zone -->
            <div id="drop" class="card card-trust dropzone border-2 border-dashed border-secondary p-5 text-center" role="button" tabindex="0" aria-label="Area upload: tarik file ke sini atau klik untuk pilih">
                <i class="bi bi-cloud-arrow-up fs-1 text-secondary mb-3"></i>
                <p class="mb-2 fw-medium">Tarik & letakkan gambar di sini, atau klik untuk pilih</p>
                <input id="files" type="file" accept=".jpg,.jpeg,.png,.gif,.webp" multiple class="d-none" aria-label="Pilih file gambar">
                <div id="preview" class="row g-3 mt-4 justify-content-center"></div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-3 d-none" id="progressContainer">
                <div class="progress progress-upload" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    <div id="bar" class="progress-bar bg-primary"></div>
                </div>
                <div class="d-flex justify-content-between small text-secondary mt-1">
                    <span id="progressText">0%</span>
                    <span id="uploadSpeed"></span>
                </div>
            </div>

            <button id="go" class="btn btn-primary w-100 mt-3" disabled><i class="bi bi-cloud-arrow-up me-1"></i>Upload</button>

            <div class="mt-3">
                <label class="form-label small text-secondary">Hapus otomatis</label>
                <select id="exp" class="form-select form-select-sm w-auto d-inline-block" aria-label="Expiry">
                    <option value="never">Tidak pernah</option>
                    <option value="1h">1 jam</option>
                    <option value="1d">1 hari</option>
                    <option value="7d">7 hari</option>
                    <option value="30d">30 hari</option>
                </select>
            </div>

            <div id="result" class="mt-4"></div>

            <div class="mt-4">
                <a href="/gallery.php" class="text-primary text-decoration-none fw-medium"><i class="bi bi-images me-1"></i>Lihat gallery</a>
            </div>
        </main>
    </div>
</div>

<!-- Upload Result Modal -->
<div class="modal fade" id="uploadResultModal" tabindex="-1" aria-labelledby="uploadResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="uploadResultModalLabel"><i class="bi bi-check-circle-fill text-success me-2"></i>Upload Berhasil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4" id="uploadResultBody"></div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="/gallery.php" class="btn btn-primary">Ke Gallery</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body py-4">
                <p class="mb-0">Apakah Anda yakin ingin menghapus gambar ini? Tindakan ini tidak bisa dibatalkan.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <form id="deleteConfirmForm" method="post">
                    <input type="hidden" name="csrf_token" value="<?= sanitize(csrfToken()) ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<script>
const drop = document.getElementById('drop');
const input = document.getElementById('files');
const prev = document.getElementById('preview');
const bar = document.getElementById('bar');
const progressContainer = document.getElementById('progressContainer');
const progressText = document.getElementById('progressText');
const uploadSpeed = document.getElementById('uploadSpeed');
const go = document.getElementById('go');
const result = document.getElementById('result');
const CSRF = <?= json_encode($token) ?>;
let files = [];

// Click / keyboard to select files
drop.addEventListener('click', () => input.click());
drop.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
});

// Drag & Drop events
['dragover', 'dragenter'].forEach(e => drop.addEventListener(e, x => {
    x.preventDefault();
    drop.classList.add('drag-active');
}));
['dragleave', 'drop'].forEach(e => drop.addEventListener(e, x => {
    x.preventDefault();
    drop.classList.remove('drag-active');
}));
drop.addEventListener('drop', x => add([...x.dataTransfer.files]));
input.addEventListener('change', () => add([...input.files]));

function add(list) {
    const valid = [];
    list.forEach(f => {
        if (!f.type.startsWith('image/')) { showToast(f.name + ' bukan gambar', 'error'); return; }
        if (f.size > 5242880) { showToast(f.name + ' melebihi 5 MB', 'error'); return; }
        valid.push(f);
    });
    // Reset input agar file yang sama bisa dipilih ulang (change tak fire dua kali)
    input.value = '';
    if (files.length + valid.length > 5) {
        showToast('Maksimal 5 file sekaligus — kelebihan diabaikan', 'error');
    }
    files = [...files, ...valid].slice(0, 5);
    prev.innerHTML = '';
    go.disabled = files.length === 0;

    files.forEach(f => {
        const col = document.createElement('div');
        col.className = 'col-6 col-md-3';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.loading = 'lazy';
        img.alt = f.name;
        img.className = 'w-100 rounded border thumb-preview';
        col.appendChild(img);
        prev.appendChild(col);
    });
}
// Klik preview jangan ikut membuka dialog file
prev.addEventListener('click', e => e.stopPropagation());

go.addEventListener('click', () => {
    if (!files.length) { showToast('Pilih file dulu', 'error'); return; }
    
    const fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('expires_in', document.getElementById('exp').value);
    files.forEach(f => fd.append('images[]', f));
    
    progressContainer.classList.remove('d-none');
    bar.style.width = '0%';
    progressText.textContent = '0%';
    go.disabled = true;
    go.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Mengunggah...';
    
    const xhr = new XMLHttpRequest();
    const startTime = Date.now();
    
    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            bar.style.width = percent + '%';
            bar.setAttribute('aria-valuenow', percent);
            progressText.textContent = percent + '%';
            
            const elapsed = (Date.now() - startTime) / 1000;
            const speed = e.loaded / elapsed / 1024; // KB/s
            uploadSpeed.textContent = speed > 1024 ? (speed / 1024).toFixed(1) + ' MB/s' : speed.toFixed(1) + ' KB/s';
        }
    };
    
    xhr.onload = () => {
        go.disabled = false;
        go.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i>Upload';
        progressContainer.classList.add('d-none');
        
        let r;
        try { r = JSON.parse(xhr.responseText); } 
        catch { showToast('Response tidak valid', 'error'); return; }
        
        if (!r.success) { showToast(r.error || 'Upload gagal', 'error'); return; }
        
        showToast('Upload berhasil', 'success');
        result.innerHTML = '';
        
        r.data.forEach(d => {
            const div = document.createElement('div');
            div.className = 'card card-trust mb-3';
            div.innerHTML = `
                <div class="card-body">
                    <img src="${d.url}" loading="lazy" class="w-100 rounded mb-3 border result-img">
                    ${row('Direct', d.url, 'linkDirect' + Math.random())}
                    ${row('BBCode', d.bbcode, 'linkBBCode' + Math.random())}
                    ${row('HTML', d.html, 'linkHTML' + Math.random())}
                </div>
            `;
            result.appendChild(div);
        });
        
        files = [];
        prev.innerHTML = '';
        bar.style.width = '0%';
    };
    
    xhr.onerror = () => {
        go.disabled = false;
        go.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i>Upload';
        progressContainer.classList.add('d-none');
        showToast('Jaringan gagal', 'error');
    };
    
    xhr.send(fd);
});

function row(label, val, id) {
    return `
        <div class="mb-2">
            <label class="form-label small text-secondary">${label}</label>
            <div class="input-group input-group-sm">
                <input type="text" id="${id}" readonly value="${val.replace(/"/g, '"')}" class="form-control mono small" aria-label="${label}">
                <button class="btn btn-outline-primary" type="button" data-copy-target="${id}" data-bs-toggle="tooltip" data-bs-title="Salin ke clipboard" aria-label="Salin ${label}">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
        </div>
    `;
}
</script>