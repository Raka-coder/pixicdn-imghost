<?php
/**
 * ============================================================
 * File: includes/footer.php
 * Deskripsi: Footer global dengan Bootstrap JS Bundle, inisialisasi
 *   tooltip, toast, popover, dan script custom.
 *   Pakai di akhir setiap halaman sebelum </body>:
 *     require __DIR__ . '/includes/footer.php';
 * ============================================================
 */
declare(strict_types=1);

$is_auth_page = $is_auth_page ?? false;
$is_public_page = $is_public_page ?? false;
?>
<?php if (!$is_auth_page && !$is_public_page): ?>
<!-- Footer Dashboard -->
<footer class="bg-white border-top border-2 border-primary mt-auto">
    <div class="container-fluid px-4 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <p class="mb-0 text-secondary small"><span class="fw-bold text-primary">PixiCDN</span> — self-hosted image hosting.</p>
            <div class="d-flex gap-4">
                <a href="/auth/register.php" class="text-secondary text-decoration-none fw-medium small">Upload</a>
                <a href="/gallery.php" class="text-secondary text-decoration-none fw-medium small">Gallery</a>
                <a href="/auth/login.php" class="text-secondary text-decoration-none fw-medium small">Masuk</a>
            </div>
        </div>
    </div>
</footer>
<?php endif; ?>

<?php if ($is_public_page): ?>
<!-- Footer Public Pages -->
<footer class="bg-white border-top border-2 border-primary mt-auto">
    <div class="container-fluid px-4 py-3 text-center">
        <p class="mb-0 text-secondary small"><span class="fw-bold text-primary">PixiCDN</span> — self-hosted image hosting.</p>
    </div>
</footer>
<?php endif; ?>

<!-- Bootstrap 5.3.3 JS Bundle via jsDelivr -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Chart.js (untuk dashboard) -->
<?php if (isset($load_chartjs) && $load_chartjs): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" integrity="sha384-7dpwGm5h9T5tW3U8ZM5/8bKpY7R8Z9Q2V3X4Y5Z6A7B8C9D0E1F2G3H4I5J6" crossorigin="anonymous"></script>
<?php endif; ?>

<script>
// ============================================================
// Global Initialization: Tooltip, Popover, Toast
// ============================================================

// Initialize all tooltips
document.addEventListener('DOMContentLoaded', function () {
    // Tooltip
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));

    // Popover
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].map(el => new bootstrap.Popover(el));

    // Auto-dismiss alerts after 5 seconds (except .alert-permanent)
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Add loading state to forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function () {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.classList.add('disabled');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
            }
        });
    });

    // Password strength meter (untuk halaman auth)
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            updatePasswordStrength(this.value);
        });
    }

    // Toggle password visibility
    document.querySelectorAll('[data-bs-toggle="password"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.bsTarget;
            const input = document.getElementById(targetId);
            if (input) {
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            }
        });
    });

    // Copy to clipboard with tooltip feedback (klik + keyboard Enter/Space)
    document.querySelectorAll('[data-copy-target]').forEach(btn => {
        btn.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ' ') && this.tagName !== 'BUTTON') {
                e.preventDefault();
                this.click();
            }
        });
        btn.addEventListener('click', async function () {
            const targetId = this.dataset.copyTarget;
            const target = document.getElementById(targetId);
            if (target) {
                const text = target.value || target.textContent;
                try {
                    await navigator.clipboard.writeText(text);
                    const tooltip = bootstrap.Tooltip.getOrCreateInstance(this);
                    const originalTitle = this.dataset.bsOriginalTitle;
                    this.dataset.bsOriginalTitle = this.getAttribute('data-bs-title');
                    this.setAttribute('data-bs-title', 'Disalin!');
                    tooltip.show();
                    setTimeout(() => {
                        tooltip.hide();
                        this.setAttribute('data-bs-title', this.dataset.bsOriginalTitle);
                    }, 1500);
                } catch (e) {
                    console.error('Copy failed:', e);
                }
            }
        });
    });

    // Modal delete confirmation
    document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#deleteModal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const form = document.getElementById('deleteConfirmForm');
            if (form && this.dataset.actionUrl) {
                form.action = this.dataset.actionUrl;
            }
        });
    });

    // Image preview modal
    document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#imagePreviewModal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = document.getElementById('imagePreviewModal');
            const img = modal.querySelector('img');
            const title = modal.querySelector('.modal-title');
            if (img && this.dataset.imageUrl) img.src = this.dataset.imageUrl;
            if (title && this.dataset.imageTitle) title.textContent = this.dataset.imageTitle;
        });
    });
});

// Password strength meter
function updatePasswordStrength(password) {
    const meter = document.getElementById('passwordStrength');
    const label = document.getElementById('passwordStrengthLabel');
    if (!meter || !label) return;

    let strength = 0;
    let labelText = 'Kekuatan: ';
    let bgClass = 'bg-danger';

    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    switch (strength) {
        case 0:
        case 1:
            labelText += 'Sangat Lemah';
            meter.style.width = '20%';
            bgClass = 'bg-danger';
            break;
        case 2:
            labelText += 'Lemah';
            meter.style.width = '40%';
            bgClass = 'bg-warning';
            break;
        case 3:
            labelText += 'Sedang';
            meter.style.width = '60%';
            bgClass = 'bg-info';
            break;
        case 4:
            labelText += 'Kuat';
            meter.style.width = '80%';
            bgClass = 'bg-primary';
            break;
        case 5:
            labelText += 'Sangat Kuat';
            meter.style.width = '100%';
            bgClass = 'bg-success';
            break;
    }

    meter.className = `progress-bar ${bgClass} progress-bar-striped progress-bar-animated`;
    meter.setAttribute('aria-valuenow', strength * 20);
    label.textContent = labelText;
}

// Show toast notification
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
        </div>
    `;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1080';
    document.body.appendChild(container);
    return container;
}
</script>
</body>
</html>