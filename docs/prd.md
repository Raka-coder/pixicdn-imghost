
# 📋 PRD (Product Requirements Document) — PixiCDN

**Versi:** 1.0
**Tanggal:** 10 Oktober 2026
**Status:** Draft untuk Implementasi
**Author:** Raka Restu Saputra

---

## 1. Executive Summary

### 1.1 Product Overview

**PixiCDN** adalah layanan SaaS (Software as a Service) berbasis web untuk **hosting gambar** dengan **direct link** dan **CDN (Content Delivery Network)**. Pengguna dapat mengupload gambar, mendapatkan link permanen yang dapat di-embed di forum, blog, atau media sosial, serta mengelola gambar melalui dashboard pribadi.

### 1.2 Problem Statement

| Masalah | Dampak |
|:---|:---|
| Layanan image hosting gratis (Imgur, dll.) sering membatasi bandwidth | User tidak bisa embed gambar secara bebas |
| Layanan populer bisa menghapus gambar tanpa pemberitahuan | Konten user hilang |
| Privacy concern — data disimpan di server pihak ketiga | User kehilangan kontrol |
| Layanan premium mahal untuk kebutuhan kecil | Overkill untuk user personal |

### 1.3 Solution

PixiCDN menyediakan:
- **Self-hosted** — data di server sendiri, kontrol penuh
- **Gratis** — tanpa biaya, tanpa iklan
- **Direct link permanen** — bisa di-embed di mana saja
- **API** — untuk integrasi developer
- **Custom domain** — `pixicdn.erdtreeproject.my.id`

### 1.4 Target User

| Persona | Kebutuhan | Fitur Utama |
|:---|:---|:---|
| **Forum User** | Upload screenshot untuk posting | Direct link, BBCode |
| **Blogger** | Host gambar untuk artikel | Direct link, thumbnail |
| **Developer** | API untuk upload otomatis | API key, REST API |
| **Content Creator** | Kelola galeri foto | Dashboard, album |
| **Mahasiswa** | Bukti tugas, dokumentasi | Upload, direct link |

---

## 2. Goals & Objectives

### 2.1 Business Goals

| # | Goal | Metrik |
|:---|:---|:---|
| 1 | Menyediakan layanan image hosting gratis | 100+ gambar terupload (target UTS) |
| 2 | Memanfaatkan infrastruktur cloud yang sudah dibangun | 100% uptime selama demo |
| 3 | Menunjukkan implementasi SaaS di atas IaaS + PaaS | Nilai A untuk UTS |

### 2.2 User Goals

| # | Goal | Metrik |
|:---|:---|:---|
| 1 | Upload gambar dengan cepat | Upload < 3 detik untuk 1 MB |
| 2 | Dapatkan direct link yang bisa di-copy | Copy < 1 detik |
| 3 | Kelola gambar dengan mudah | Dashboard intuitif |
| 4 | Akses gambar dengan cepat | Load < 2 detik via CDN |

### 2.3 Non-Goals (Out of Scope)

- ❌ Payment gateway / billing
- ❌ Subscription / monetisasi
- ❌ AI tagging / OCR
- ❌ Blockchain proof
- ❌ Image editor (crop, filter)
- ❌ Video hosting
- ❌ Social features (like, comment, follow)

---

## 3. Features & Requirements

### 3.1 Feature Priority Matrix

| Fase | Fitur | Prioritas | Estimasi |
|:---|:---|:---|:---|
| **Fase 1** | Upload anonymous | 🥇 P0 | 2 jam |
| | Direct link | 🥇 P0 | 1 jam |
| | Gallery | 🥇 P0 | 2 jam |
| | Delete image | 🥇 P0 | 1 jam |
| | View counter | 🥈 P1 | 1 jam |
| **Fase 2** | User registration | 🥇 P0 | 2 jam |
| | Login/logout | 🥇 P0 | 2 jam |
| | User dashboard | 🥇 P0 | 3 jam |
| | Profile (edit username, ubah password) | 🥇 P0 | 2 jam |
| | Forgot password | 🥇 P0 | 2 jam |
| | Reset password | 🥇 P0 | 1 jam |
| | API key management | 🥈 P1 | 2 jam |
| | REST API | 🥈 P1 | 3 jam |
| | Thumbnail generation | 🥉 P2 | 2 jam |
| **Fase 3** | Album management | 🥈 P1 | 4 jam |
| | Analytics dashboard | 🥈 P1 | 3 jam |
| | Expiry settings | 🥉 P2 | 2 jam |

### 3.2 Detailed Feature Specification

#### 3.2.1 Upload Anonymous (Fase 1)

**Deskripsi:** User dapat upload gambar tanpa perlu login.

**User Story:**
> Sebagai user, saya ingin upload gambar tanpa login agar bisa cepat mendapatkan direct link.

**Acceptance Criteria:**
- [ ] User dapat drag & drop gambar ke upload zone
- [ ] User dapat klik untuk pilih file
- [ ] Mendukung multiple file (max 5 file sekaligus)
- [ ] Format: JPG, JPEG, PNG, GIF, WebP
- [ ] Max size: 5 MB per file
- [ ] Preview gambar sebelum upload
- [ ] Validasi client-side (via FileBokz)
- [ ] Validasi server-side (PHP)
- [ ] Progress bar saat upload
- [ ] Notifikasi toast saat sukses/gagal

**UI Elements:**
- Upload zone (FileBokz)
- Submit button
- Loading spinner
- Toast notification (Toastify)

#### 3.2.2 Direct Link (Fase 1)

**Deskripsi:** Setelah upload, user mendapatkan URL permanen untuk gambar.

**User Story:**
> Sebagai user, saya ingin mendapatkan direct link agar bisa embed gambar di forum.

**Acceptance Criteria:**
- [ ] Setelah upload, tampilkan URL dalam 3 format:
  - **Direct Link:** `https://pixicdn.erdtreeproject.my.id/abc123.jpg`
  - **BBCode:** `[img]https://pixicdn.erdtreeproject.my.id/abc123.jpg[/img]`
  - **HTML:** `<img src="https://pixicdn.erdtreeproject.my.id/abc123.jpg">`
- [ ] Tombol "Copy" di setiap format
- [ ] Feedback visual saat copy (ubah teks jadi "Copied!")
- [ ] Preview gambar di samping link

**UI Elements:**
- Preview card
- 3 input field (read-only) dengan tombol copy
- Toast notification saat copy

#### 3.2.3 Gallery (Fase 1)

**Deskripsi:** Halaman untuk melihat semua gambar yang di-upload.

**User Story:**
> Sebagai user, saya ingin melihat galeri gambar agar bisa mengelola koleksi saya.

**Acceptance Criteria:**
- [ ] Grid responsive (2 kolom mobile, 4 kolom desktop)
- [ ] Lazy loading untuk gambar
- [ ] Filter: All, Today, This Week, This Month
- [ ] Sort: Newest, Oldest, Most Viewed
- [ ] Pagination (20 gambar per halaman)
- [ ] Klik gambar → buka direct link viewer
- [ ] Hover → tampilkan view count + tombol delete

**UI Elements:**
- Filter bar
- Image grid
- Pagination
- Empty state (jika belum ada gambar)

#### 3.2.4 Delete Image (Fase 1)

**Deskripsi:** User dapat menghapus gambar yang sudah di-upload.

**User Story:**
> Sebagai user, saya ingin menghapus gambar agar bisa mengelola storage.

**Acceptance Criteria:**
- [ ] Tombol delete di setiap gambar
- [ ] Konfirmasi sebelum delete (modal)
- [ ] Hapus file dari RustFS
- [ ] Hapus metadata dari MariaDB
- [ ] Toast notification setelah delete
- [ ] Refresh gallery otomatis

**UI Elements:**
- Delete button (icon trash)
- Confirmation modal (SweetAlert2 atau native)
- Toast notification

#### 3.2.5 View Counter (Fase 1)

**Deskripsi:** Hitung berapa kali gambar dilihat.

**User Story:**
> Sebagai user, saya ingin tahu berapa kali gambar saya dilihat.

**Acceptance Criteria:**
- [ ] Setiap akses gambar → increment counter
- [ ] Tampilkan view count di gallery
- [ ] Tampilkan view count di dashboard
- [ ] Hindari spam (1 IP = 1 view per jam)

#### 3.2.6 User Registration (Fase 2)

**Deskripsi:** User dapat mendaftar dengan username dan password.

**User Story:**
> Sebagai user baru, saya ingin mendaftar agar bisa mengelola gambar saya.

**Acceptance Criteria:**
- [ ] Form: Username + Password + Confirm Password
- [ ] Validasi username: 3-30 karakter, huruf/angka/underscore
- [ ] Validasi password: minimal 8 karakter
- [ ] Cek username sudah dipakai atau belum
- [ ] Hash password dengan bcrypt (cost 12)
- [ ] Auto-login setelah register
- [ ] Toast notification sukses

**UI Elements:**
- Form register
- Password strength indicator (opsional)
- Show/hide password toggle
- Toast notification

#### 3.2.7 Login/Logout (Fase 2)

**Deskripsi:** User dapat login dan logout.

**User Story:**
> Sebagai user terdaftar, saya ingin login agar bisa akses dashboard.

**Acceptance Criteria:**
- [ ] Form: Username + Password
- [ ] Validasi kredensial
- [ ] Session regenerate setelah login (cegah session fixation)
- [ ] Remember me (opsional, 30 hari)
- [ ] Redirect ke dashboard setelah login
- [ ] Logout → hapus session → redirect ke landing
- [ ] Toast notification sukses/gagal

**UI Elements:**
- Form login
- Show/hide password toggle
- "Lupa password?" link
- "Belum punya akun? Daftar" link

#### 3.2.8 User Dashboard (Fase 2)

**Deskripsi:** Halaman utama user setelah login.

**User Story:**
> Sebagai user, saya ingin melihat statistik dan mengelola gambar saya.

**Acceptance Criteria:**
- [ ] Statistik: Total images, Storage used, Total views
- [ ] Recent uploads (6 gambar terbaru)
- [ ] Quick actions: Upload, Gallery, Settings
- [ ] Grafik upload per hari (Chart.js)
- [ ] Navigation sidebar

**UI Elements:**
- Stats cards (3 kartu)
- Recent uploads grid
- Chart (opsional)
- Sidebar navigation

#### 3.2.9 Profile (Fase 2)

**Deskripsi:** User dapat melihat dan mengedit profil.

**User Story:**
> Sebagai user, saya ingin mengubah username dan password saya.

**Acceptance Criteria:**
- [ ] Tampilkan info user (username, join date, total images)
- [ ] Edit username
- [ ] Ubah password (perlu password lama)
- [ ] Upload avatar (opsional)
- [ ] Toast notification sukses/gagal

**UI Elements:**
- Profile card
- Form edit username
- Form ubah password
- Avatar upload (opsional)

#### 3.2.10 Forgot Password (Fase 2)

**Deskripsi:** User dapat request reset password jika lupa.

**User Story:**
> Sebagai user yang lupa password, saya ingin reset password agar bisa login kembali.

**Acceptance Criteria:**
- [ ] Form: Username
- [ ] Generate token: `bin2hex(random_bytes(32))`
- [ ] Simpan token hash (SHA-256) di database
- [ ] Token expire dalam 1 jam
- [ ] Response sama, baik username ada atau tidak (no enumeration)
- [ ] "Kirim" link reset ke... **Bagaimana?** ← **Perlu klarifikasi**

> **⚠️ PERTANYAAN PENTING:** Karena user hanya pakai username (tanpa email), **bagaimana cara kirim link reset password?**
> 
> **Opsi:**
> 1. **Admin panel** — admin kirim manual
> 2. **Security question** — user jawab pertanyaan keamanan
> 3. **Recovery code** — user simpan kode saat register
> 4. **Email** — user isi email opsional saat register
> 
> **Rekomendasi:** **Opsi 3 (Recovery Code)** — saat register, tampilkan 8-digit recovery code yang harus disimpan user. Jika lupa password, masukkan recovery code.

#### 3.2.11 Reset Password (Fase 2)

**Deskripsi:** User dapat set password baru via token/code.

**User Story:**
> Sebagai user, saya ingin set password baru agar bisa login lagi.

**Acceptance Criteria:**
- [ ] Form: Recovery code + Password baru + Confirm password
- [ ] Validasi recovery code
- [ ] Validasi password baru (min 8 karakter)
- [ ] Update password hash di database
- [ ] Mark token/code as used
- [ ] Redirect ke login
- [ ] Toast notification sukses

#### 3.2.12 API Key Management (Fase 2)

**Deskripsi:** User dapat generate API key untuk akses API.

**User Story:**
> Sebagai developer, saya ingin API key agar bisa upload via program.

**Acceptance Criteria:**
- [ ] Generate API key (64 karakter random)
- [ ] Tampilkan API key sekali saja (tidak bisa dilihat lagi)
- [ ] Copy API key ke clipboard
- [ ] Regenerate API key
- [ ] Hapus API key
- [ ] API key bisa dipakai di header `X-API-Key`

#### 3.2.13 REST API (Fase 2)

**Deskripsi:** API untuk upload gambar programmatic.

**User Story:**
> Sebagai developer, saya ingin upload gambar via API agar bisa integrasi ke aplikasi lain.

**Acceptance Criteria:**

| Endpoint | Method | Fungsi |
|:---|:---|:---|
| `/api/upload` | POST | Upload gambar |
| `/api/images` | GET | List gambar user |
| `/api/images/{id}` | DELETE | Hapus gambar |
| `/api/me` | GET | Info user |

**Headers:**
- `X-API-Key: <api_key>` (wajib)

**Response Format:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "url": "https://pixicdn.erdtreeproject.my.id/abc123.jpg",
    "size": 45678,
    "views": 0
  }
}
```

#### 3.2.14 Thumbnail Generation (Fase 2)

**Deskripsi:** Auto-generate thumbnail untuk gambar yang di-upload.

**Acceptance Criteria:**
- [ ] Generate thumbnail 300x300 (max dimension)
- [ ] Simpan thumbnail di RustFS dengan prefix `thumb_`
- [ ] Tampilkan thumbnail di gallery (bukan gambar asli)
- [ ] Klik thumbnail → buka gambar asli

---

## 4. Technical Requirements

### 4.1 Tech Stack

| Layer | Teknologi | Lokasi |
|:---|:---|:---|
| **Frontend** | HTML5, CSS3 (Tailwind v4), Vanilla JS | Server 2 |
| **Backend** | PHP 8.2 Native | Server 2 |
| **Web Server** | Caddy | Server 2 |
| **Database** | MariaDB 11 | Server 3 |
| **Object Storage** | RustFS | Server 3 |
| **CDN** | Cloudflare | - |
| **Container** | Podman + Quadlet | Server 2 & 3 |

### 4.2 Library & Tools

| Kebutuhan | Library | CDN |
|:---|:---|:---|
| **Styling** | Tailwind CSS v4 | jsDelivr |
| **Icons** | Lucide | unpkg |
| **Toast** | Toastify (zzev) | jsDelivr |
| **Upload** | FileBokz | jsDelivr |
| **Modal** | SweetAlert2 | jsDelivr |
| **Chart** | Chart.js | jsDelivr |
| **Backend** | AWS SDK for PHP | Composer |
| **Email** (opsional) | PHPMailer | Composer |

### 4.3 Browser Support

| Browser | Versi Minimum |
|:---|:---|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |
| Mobile Chrome | 90+ |
| Mobile Safari | 14+ |

### 4.4 Performance Requirements

| Metrik | Target |
|:---|:---|
| **Upload time** (1 MB) | < 3 detik |
| **Page load** | < 2 detik |
| **Image load** (via CDN) | < 1 detik |
| **API response** | < 500 ms |
| **Uptime** | 99% |

### 4.5 Security Requirements

| Aspek | Implementasi |
|:---|:---|
| **Password hashing** | bcrypt (cost 12) |
| **Session** | `httponly`, `secure`, `samesite=Lax` |
| **CSRF protection** | Token di setiap form |
| **SQL injection** | Prepared statements (PDO) |
| **XSS** | `htmlspecialchars()` untuk output |
| **File upload** | Validasi MIME type + extension |
| **API key** | 64 karakter random |
| **Rate limiting** | 100 upload/jam per IP |

### 4.6 Storage Requirements

| Item | Limit |
|:---|:---|
| **Max file size** | 5 MB |
| **Max files per upload** | 5 file |
| **Storage per user** | 100 MB (default) |
| **Allowed formats** | JPG, JPEG, PNG, GIF, WebP |
| **Thumbnail size** | 300x300 (max) |

---

## 5. Architecture Overview

### 5.1 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  User Browser                                                │
│  ─ Tailwind CSS, FileBokz, Toastify, Lucide                 │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTPS
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  Cloudflare Edge (CDN + SSL)                                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  Server 2 (192.168.1.48)                                    │
│  ─ Caddy + PHP-FPM                                          │
│  ─ Frontend + Backend logic                                 │
└──────────┬────────────────────────────────┬─────────────────┘
           │                                │
           │ PDO (MariaDB)                  │ S3 API (RustFS)
           ▼                                ▼
┌──────────────────────┐     ┌──────────────────────────────┐
│  Server 3 (MariaDB)  │     │  Server 3 (RustFS)           │
│  ─ users             │     │  ─ Bucket: pixicdn           │
│  ─ images            │     │  ─ File gambar + thumbnail   │
│  ─ password_resets   │     │                              │
└──────────────────────┘     └──────────────────────────────┘
```

### 5.2 Data Flow — Upload

```
1. User pilih gambar (FileBokz)
2. POST /upload.php (multipart/form-data)
3. PHP validasi (tipe, ukuran, dll)
4. PHP generate nama unik: 20261010_abc123.jpg
5. PHP upload ke RustFS via AWS SDK
6. RustFS simpan di bucket "pixicdn"
7. PHP generate thumbnail (opsional)
8. PHP simpan metadata ke MariaDB
9. PHP return URL ke user
10. Toastify tampilkan notifikasi sukses
```

### 5.3 Data Flow — Akses Gambar

```
1. User akses https://pixicdn.erdtreeproject.my.id/abc123.jpg
2. Cloudflare Edge (cache jika ada)
3. Cloudflare Tunnel → Server 3 (RustFS :9000)
4. RustFS serve gambar
5. Gambar ditampilkan
6. View counter di-increment (async)
```

---

## 6. Database Schema

### 6.1 Tabel `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    recovery_code_hash VARCHAR(255) NULL,
    api_key VARCHAR(64) UNIQUE NULL,
    storage_quota BIGINT DEFAULT 104857600,
    storage_used BIGINT DEFAULT 0,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_api_key (api_key)
);
```

### 6.2 Tabel `images`

```sql
CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    s3_key VARCHAR(500) NOT NULL,
    public_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    mime_type VARCHAR(50),
    size INT,
    width INT,
    height INT,
    views INT DEFAULT 0,
    ip_address VARCHAR(45),
    is_public BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_filename (filename),
    INDEX idx_user (user_id),
    INDEX idx_uploaded (uploaded_at)
);
```

### 6.3 Tabel `password_resets`

```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user (user_id)
);
```

### 6.4 Tabel `view_logs`

```sql
CREATE TABLE view_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    INDEX idx_image (image_id),
    INDEX idx_viewed (viewed_at)
);
```

---

## 7. UI/UX Design Principles

### 7.1 Design System

| Aspek | Nilai |
|:---|:---|
| **Tema** | Dark mode (default), Light mode (opsional) |
| **Warna Utama** | Indigo (#667eea) → Purple (#764ba2) gradient |
| **Warna Background** | Slate-950 (#0a0e27) |
| **Font** | Inter atau system-ui |
| **Border Radius** | 8px (small), 12px (medium), 16px (large) |
| **Spacing** | 4px, 8px, 16px, 24px, 32px, 48px |

### 7.2 Component Library

| Komponen | Library |
|:---|:---|
| **Button** | Tailwind |
| **Input** | Tailwind |
| **Card** | Tailwind |
| **Modal** | SweetAlert2 |
| **Toast** | Toastify (zzev) |
| **Upload Zone** | FileBokz |
| **Icon** | Lucide |
| **Chart** | Chart.js |

### 7.3 Responsive Breakpoints

| Breakpoint | Width | Layout |
|:---|:---|:---|
| **Mobile** | < 640px | 1 kolom |
| **Tablet** | 640-1024px | 2 kolom |
| **Desktop** | > 1024px | 3-4 kolom |

### 7.4 Accessibility

- Kontras warna WCAG AA
- Keyboard navigation
- ARIA labels
- Alt text untuk gambar
- Focus indicators

---

## 8. Success Metrics

### 8.1 Technical Metrics

| Metrik | Target | Cara Ukur |
|:---|:---|:---|
| **Uptime** | 99% | Uptime Kuma |
| **Upload success rate** | 99% | Log PHP |
| **Average upload time** | < 3 detik | Timer PHP |
| **API response time** | < 500 ms | Log API |
| **Error rate** | < 1% | Log error |

### 8.2 User Metrics

| Metrik | Target |
|:---|:---|
| **Total users** | 10+ (target UTS) |
| **Total images** | 100+ (target UTS) |
| **Total views** | 1000+ (target UTS) |
| **User retention** | 50% (login kembali) |

### 8.3 Business Metrics

| Metrik | Target |
|:---|:---|
| **Demo UTS** | Berjalan lancar |
| **Nilai UTS** | A |
| **Portfolio value** | Bisa dipamerkan |

---

## 9. Timeline & Milestones

### 9.1 Development Timeline

| Hari | Fokus | Deliverable |
|:---|:---|:---|
| **Hari 1** | Setup | Database, tabel, Composer, config |
| **Hari 2** | Auth | Register, login, logout, session |
| **Hari 3** | Upload | Upload form, handler, gallery |
| **Hari 4** | Akun | Profile, ubah password, forgot password |
| **Hari 5** | Polish | Dashboard, API, testing, screenshot |
| **Hari 6** | Dokumentasi | Laporan, PPT, video demo |
| **Hari 7** | Buffer | Perbaikan, latihan demo |

### 9.2 Milestones

| Milestone | Target | Status |
|:---|:---|:---|
| **M1: Database Ready** | Hari 1 | ⏳ |
| **M2: Auth Working** | Hari 2 | ⏳ |
| **M3: Upload Working** | Hari 3 | ⏳ |
| **M4: Account Management** | Hari 4 | ⏳ |
| **M5: MVP Complete** | Hari 5 | ⏳ |
| **M6: Demo Ready** | Hari 7 | ⏳ |

---

## 10. Risks & Mitigation

| # | Risiko | Probabilitas | Dampak | Mitigasi |
|:---|:---|:---|:---|:---|
| 1 | **Storage penuh** | Sedang | Tinggi | Monitor via Beszel, limit 100 MB/user |
| 2 | **RustFS down** | Rendah | Tinggi | Monitoring + alert |
| 3 | **Upload gagal** | Sedang | Sedang | Retry logic + toast error |
| 4 | **Session hijacking** | Rendah | Tinggi | Secure cookies + regenerate |
| 5 | **SQL injection** | Rendah | Tinggi | Prepared statements |
| 6 | **Waktu tidak cukup** | Sedang | Tinggi | Fokus MVP dulu |
| 7 | **Bug saat demo** | Sedang | Tinggi | Video cadangan + latihan |

---

## 11. Open Questions

| # | Pertanyaan | Perlu Keputusan |
|:---|:---|:---|
| 1 | **Cara reset password tanpa email?** | Recovery code? Security question? |
| 2 | **Apakah perlu admin panel?** | Untuk moderate gambar? |
| 3 | **Apakah perlu auto-delete gambar lama?** | Setelah X hari? |
| 4 | **Apakah perlu watermark?** | Untuk gambar tertentu? |
| 5 | **Apakah perlu multi-bahasa?** | ID + EN? |

---

## 12. Appendix

### 12.1 Glossary

| Istilah | Definisi |
|:---|:---|
| **CDN** | Content Delivery Network |
| **SaaS** | Software as a Service |
| **Direct Link** | URL langsung ke file gambar |
| **BBCode** | Format markup untuk forum |
| **Thumbnail** | Versi kecil dari gambar |
| **API Key** | Kunci untuk akses API |

### 12.2 References

- [Tailwind CSS v4 Docs](https://tailwindcss.com/docs)
- [FileBokz GitHub](https://github.com/fivefifteen/filebokz)
- [Toastify (zzev) GitHub](https://github.com/zzev/toastify)
- [Lucide Icons](https://lucide.dev/)
- [AWS SDK for PHP](https://docs.aws.amazon.com/sdk-for-php/)
- [OWASP Password Storage](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)

---

## 🎯 Kesimpulan

PRD ini mencakup:
- ✅ **12 section** lengkap
- ✅ **14 fitur** terdefinisi dengan acceptance criteria
- ✅ **Tech stack** dan library final
- ✅ **Database schema** lengkap
- ✅ **UI/UX principles**
- ✅ **Timeline** 7 hari
- ✅ **Risk mitigation**

**Yang perlu Anda putuskan:**
1. **Cara reset password** tanpa email (recovery code / security question / lain)
2. **Apakah perlu admin panel**
3. **Apakah perlu auto-delete** gambar lama
4. **Apakah perlu watermark**

---

**Apakah PRD ini sudah sesuai?** Jika ya, saya bisa lanjut ke:
1. **📐 Architecture Document** — diagram teknis lengkap
2. **📋 Rules Document** — coding standards + security rules
3. **🗄️ Schema Document** — database schema detail + migration

Atau ada bagian yang ingin direvisi dulu? 🚀# 📋 PRD (Product Requirements Document) — PixiCDN

**Versi:** 1.0
**Tanggal:** 10 Oktober 2026
**Status:** Draft untuk Implementasi
**Author:** Raka Restu Saputra

---

## 1. Executive Summary

### 1.1 Product Overview

**PixiCDN** adalah layanan SaaS (Software as a Service) berbasis web untuk **hosting gambar** dengan **direct link** dan **CDN (Content Delivery Network)**. Pengguna dapat mengupload gambar, mendapatkan link permanen yang dapat di-embed di forum, blog, atau media sosial, serta mengelola gambar melalui dashboard pribadi.

### 1.2 Problem Statement

| Masalah | Dampak |
|:---|:---|
| Layanan image hosting gratis (Imgur, dll.) sering membatasi bandwidth | User tidak bisa embed gambar secara bebas |
| Layanan populer bisa menghapus gambar tanpa pemberitahuan | Konten user hilang |
| Privacy concern — data disimpan di server pihak ketiga | User kehilangan kontrol |
| Layanan premium mahal untuk kebutuhan kecil | Overkill untuk user personal |

### 1.3 Solution

PixiCDN menyediakan:
- **Self-hosted** — data di server sendiri, kontrol penuh
- **Gratis** — tanpa biaya, tanpa iklan
- **Direct link permanen** — bisa di-embed di mana saja
- **API** — untuk integrasi developer
- **Custom domain** — `pixicdn.erdtreeproject.my.id`

### 1.4 Target User

| Persona | Kebutuhan | Fitur Utama |
|:---|:---|:---|
| **Forum User** | Upload screenshot untuk posting | Direct link, BBCode |
| **Blogger** | Host gambar untuk artikel | Direct link, thumbnail |
| **Developer** | API untuk upload otomatis | API key, REST API |
| **Content Creator** | Kelola galeri foto | Dashboard, album |
| **Mahasiswa** | Bukti tugas, dokumentasi | Upload, direct link |

---

## 2. Goals & Objectives

### 2.1 Business Goals

| # | Goal | Metrik |
|:---|:---|:---|
| 1 | Menyediakan layanan image hosting gratis | 100+ gambar terupload (target UTS) |
| 2 | Memanfaatkan infrastruktur cloud yang sudah dibangun | 100% uptime selama demo |
| 3 | Menunjukkan implementasi SaaS di atas IaaS + PaaS | Nilai A untuk UTS |

### 2.2 User Goals

| # | Goal | Metrik |
|:---|:---|:---|
| 1 | Upload gambar dengan cepat | Upload < 3 detik untuk 1 MB |
| 2 | Dapatkan direct link yang bisa di-copy | Copy < 1 detik |
| 3 | Kelola gambar dengan mudah | Dashboard intuitif |
| 4 | Akses gambar dengan cepat | Load < 2 detik via CDN |

### 2.3 Non-Goals (Out of Scope)

- ❌ Payment gateway / billing
- ❌ Subscription / monetisasi
- ❌ AI tagging / OCR
- ❌ Blockchain proof
- ❌ Image editor (crop, filter)
- ❌ Video hosting
- ❌ Social features (like, comment, follow)

---

## 3. Features & Requirements

### 3.1 Feature Priority Matrix

| Fase | Fitur | Prioritas | Estimasi |
|:---|:---|:---|:---|
| **Fase 1** | Upload anonymous | 🥇 P0 | 2 jam |
| | Direct link | 🥇 P0 | 1 jam |
| | Gallery | 🥇 P0 | 2 jam |
| | Delete image | 🥇 P0 | 1 jam |
| | View counter | 🥈 P1 | 1 jam |
| **Fase 2** | User registration | 🥇 P0 | 2 jam |
| | Login/logout | 🥇 P0 | 2 jam |
| | User dashboard | 🥇 P0 | 3 jam |
| | Profile (edit username, ubah password) | 🥇 P0 | 2 jam |
| | Forgot password | 🥇 P0 | 2 jam |
| | Reset password | 🥇 P0 | 1 jam |
| | API key management | 🥈 P1 | 2 jam |
| | REST API | 🥈 P1 | 3 jam |
| | Thumbnail generation | 🥉 P2 | 2 jam |
| **Fase 3** | Album management | 🥈 P1 | 4 jam |
| | Analytics dashboard | 🥈 P1 | 3 jam |
| | Expiry settings | 🥉 P2 | 2 jam |

### 3.2 Detailed Feature Specification

#### 3.2.1 Upload Anonymous (Fase 1)

**Deskripsi:** User dapat upload gambar tanpa perlu login.

**User Story:**
> Sebagai user, saya ingin upload gambar tanpa login agar bisa cepat mendapatkan direct link.

**Acceptance Criteria:**
- [ ] User dapat drag & drop gambar ke upload zone
- [ ] User dapat klik untuk pilih file
- [ ] Mendukung multiple file (max 5 file sekaligus)
- [ ] Format: JPG, JPEG, PNG, GIF, WebP
- [ ] Max size: 5 MB per file
- [ ] Preview gambar sebelum upload
- [ ] Validasi client-side (via FileBokz)
- [ ] Validasi server-side (PHP)
- [ ] Progress bar saat upload
- [ ] Notifikasi toast saat sukses/gagal

**UI Elements:**
- Upload zone (FileBokz)
- Submit button
- Loading spinner
- Toast notification (Toastify)

#### 3.2.2 Direct Link (Fase 1)

**Deskripsi:** Setelah upload, user mendapatkan URL permanen untuk gambar.

**User Story:**
> Sebagai user, saya ingin mendapatkan direct link agar bisa embed gambar di forum.

**Acceptance Criteria:**
- [ ] Setelah upload, tampilkan URL dalam 3 format:
  - **Direct Link:** `https://pixicdn.erdtreeproject.my.id/abc123.jpg`
  - **BBCode:** `[img]https://pixicdn.erdtreeproject.my.id/abc123.jpg[/img]`
  - **HTML:** `<img src="https://pixicdn.erdtreeproject.my.id/abc123.jpg">`
- [ ] Tombol "Copy" di setiap format
- [ ] Feedback visual saat copy (ubah teks jadi "Copied!")
- [ ] Preview gambar di samping link

**UI Elements:**
- Preview card
- 3 input field (read-only) dengan tombol copy
- Toast notification saat copy

#### 3.2.3 Gallery (Fase 1)

**Deskripsi:** Halaman untuk melihat semua gambar yang di-upload.

**User Story:**
> Sebagai user, saya ingin melihat galeri gambar agar bisa mengelola koleksi saya.

**Acceptance Criteria:**
- [ ] Grid responsive (2 kolom mobile, 4 kolom desktop)
- [ ] Lazy loading untuk gambar
- [ ] Filter: All, Today, This Week, This Month
- [ ] Sort: Newest, Oldest, Most Viewed
- [ ] Pagination (20 gambar per halaman)
- [ ] Klik gambar → buka direct link viewer
- [ ] Hover → tampilkan view count + tombol delete

**UI Elements:**
- Filter bar
- Image grid
- Pagination
- Empty state (jika belum ada gambar)

#### 3.2.4 Delete Image (Fase 1)

**Deskripsi:** User dapat menghapus gambar yang sudah di-upload.

**User Story:**
> Sebagai user, saya ingin menghapus gambar agar bisa mengelola storage.

**Acceptance Criteria:**
- [ ] Tombol delete di setiap gambar
- [ ] Konfirmasi sebelum delete (modal)
- [ ] Hapus file dari RustFS
- [ ] Hapus metadata dari MariaDB
- [ ] Toast notification setelah delete
- [ ] Refresh gallery otomatis

**UI Elements:**
- Delete button (icon trash)
- Confirmation modal (SweetAlert2 atau native)
- Toast notification

#### 3.2.5 View Counter (Fase 1)

**Deskripsi:** Hitung berapa kali gambar dilihat.

**User Story:**
> Sebagai user, saya ingin tahu berapa kali gambar saya dilihat.

**Acceptance Criteria:**
- [ ] Setiap akses gambar → increment counter
- [ ] Tampilkan view count di gallery
- [ ] Tampilkan view count di dashboard
- [ ] Hindari spam (1 IP = 1 view per jam)

#### 3.2.6 User Registration (Fase 2)

**Deskripsi:** User dapat mendaftar dengan username dan password.

**User Story:**
> Sebagai user baru, saya ingin mendaftar agar bisa mengelola gambar saya.

**Acceptance Criteria:**
- [ ] Form: Username + Password + Confirm Password
- [ ] Validasi username: 3-30 karakter, huruf/angka/underscore
- [ ] Validasi password: minimal 8 karakter
- [ ] Cek username sudah dipakai atau belum
- [ ] Hash password dengan bcrypt (cost 12)
- [ ] Auto-login setelah register
- [ ] Toast notification sukses

**UI Elements:**
- Form register
- Password strength indicator (opsional)
- Show/hide password toggle
- Toast notification

#### 3.2.7 Login/Logout (Fase 2)

**Deskripsi:** User dapat login dan logout.

**User Story:**
> Sebagai user terdaftar, saya ingin login agar bisa akses dashboard.

**Acceptance Criteria:**
- [ ] Form: Username + Password
- [ ] Validasi kredensial
- [ ] Session regenerate setelah login (cegah session fixation)
- [ ] Remember me (opsional, 30 hari)
- [ ] Redirect ke dashboard setelah login
- [ ] Logout → hapus session → redirect ke landing
- [ ] Toast notification sukses/gagal

**UI Elements:**
- Form login
- Show/hide password toggle
- "Lupa password?" link
- "Belum punya akun? Daftar" link

#### 3.2.8 User Dashboard (Fase 2)

**Deskripsi:** Halaman utama user setelah login.

**User Story:**
> Sebagai user, saya ingin melihat statistik dan mengelola gambar saya.

**Acceptance Criteria:**
- [ ] Statistik: Total images, Storage used, Total views
- [ ] Recent uploads (6 gambar terbaru)
- [ ] Quick actions: Upload, Gallery, Settings
- [ ] Grafik upload per hari (Chart.js)
- [ ] Navigation sidebar

**UI Elements:**
- Stats cards (3 kartu)
- Recent uploads grid
- Chart (opsional)
- Sidebar navigation

#### 3.2.9 Profile (Fase 2)

**Deskripsi:** User dapat melihat dan mengedit profil.

**User Story:**
> Sebagai user, saya ingin mengubah username dan password saya.

**Acceptance Criteria:**
- [ ] Tampilkan info user (username, join date, total images)
- [ ] Edit username
- [ ] Ubah password (perlu password lama)
- [ ] Upload avatar (opsional)
- [ ] Toast notification sukses/gagal

**UI Elements:**
- Profile card
- Form edit username
- Form ubah password
- Avatar upload (opsional)

#### 3.2.10 Forgot Password (Fase 2)

**Deskripsi:** User dapat request reset password jika lupa.

**User Story:**
> Sebagai user yang lupa password, saya ingin reset password agar bisa login kembali.

**Acceptance Criteria:**
- [ ] Form: Username
- [ ] Generate token: `bin2hex(random_bytes(32))`
- [ ] Simpan token hash (SHA-256) di database
- [ ] Token expire dalam 1 jam
- [ ] Response sama, baik username ada atau tidak (no enumeration)
- [ ] "Kirim" link reset ke... **Bagaimana?** ← **Perlu klarifikasi**

> **⚠️ PERTANYAAN PENTING:** Karena user hanya pakai username (tanpa email), **bagaimana cara kirim link reset password?**
> 
> **Opsi:**
> 1. **Admin panel** — admin kirim manual
> 2. **Security question** — user jawab pertanyaan keamanan
> 3. **Recovery code** — user simpan kode saat register
> 4. **Email** — user isi email opsional saat register
> 
> **Rekomendasi:** **Opsi 3 (Recovery Code)** — saat register, tampilkan 8-digit recovery code yang harus disimpan user. Jika lupa password, masukkan recovery code.

#### 3.2.11 Reset Password (Fase 2)

**Deskripsi:** User dapat set password baru via token/code.

**User Story:**
> Sebagai user, saya ingin set password baru agar bisa login lagi.

**Acceptance Criteria:**
- [ ] Form: Recovery code + Password baru + Confirm password
- [ ] Validasi recovery code
- [ ] Validasi password baru (min 8 karakter)
- [ ] Update password hash di database
- [ ] Mark token/code as used
- [ ] Redirect ke login
- [ ] Toast notification sukses

#### 3.2.12 API Key Management (Fase 2)

**Deskripsi:** User dapat generate API key untuk akses API.

**User Story:**
> Sebagai developer, saya ingin API key agar bisa upload via program.

**Acceptance Criteria:**
- [ ] Generate API key (64 karakter random)
- [ ] Tampilkan API key sekali saja (tidak bisa dilihat lagi)
- [ ] Copy API key ke clipboard
- [ ] Regenerate API key
- [ ] Hapus API key
- [ ] API key bisa dipakai di header `X-API-Key`

#### 3.2.13 REST API (Fase 2)

**Deskripsi:** API untuk upload gambar programmatic.

**User Story:**
> Sebagai developer, saya ingin upload gambar via API agar bisa integrasi ke aplikasi lain.

**Acceptance Criteria:**

| Endpoint | Method | Fungsi |
|:---|:---|:---|
| `/api/upload` | POST | Upload gambar |
| `/api/images` | GET | List gambar user |
| `/api/images/{id}` | DELETE | Hapus gambar |
| `/api/me` | GET | Info user |

**Headers:**
- `X-API-Key: <api_key>` (wajib)

**Response Format:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "url": "https://pixicdn.erdtreeproject.my.id/abc123.jpg",
    "size": 45678,
    "views": 0
  }
}
```

#### 3.2.14 Thumbnail Generation (Fase 2)

**Deskripsi:** Auto-generate thumbnail untuk gambar yang di-upload.

**Acceptance Criteria:**
- [ ] Generate thumbnail 300x300 (max dimension)
- [ ] Simpan thumbnail di RustFS dengan prefix `thumb_`
- [ ] Tampilkan thumbnail di gallery (bukan gambar asli)
- [ ] Klik thumbnail → buka gambar asli

---

## 4. Technical Requirements

### 4.1 Tech Stack

| Layer | Teknologi | Lokasi |
|:---|:---|:---|
| **Frontend** | HTML5, CSS3 (Tailwind v4), Vanilla JS | Server 2 |
| **Backend** | PHP 8.2 Native | Server 2 |
| **Web Server** | Caddy | Server 2 |
| **Database** | MariaDB 11 | Server 3 |
| **Object Storage** | RustFS | Server 3 |
| **CDN** | Cloudflare | - |
| **Container** | Podman + Quadlet | Server 2 & 3 |

### 4.2 Library & Tools

| Kebutuhan | Library | CDN |
|:---|:---|:---|
| **Styling** | Tailwind CSS v4 | jsDelivr |
| **Icons** | Lucide | unpkg |
| **Toast** | Toastify (zzev) | jsDelivr |
| **Upload** | FileBokz | jsDelivr |
| **Modal** | SweetAlert2 | jsDelivr |
| **Chart** | Chart.js | jsDelivr |
| **Backend** | AWS SDK for PHP | Composer |
| **Email** (opsional) | PHPMailer | Composer |

### 4.3 Browser Support

| Browser | Versi Minimum |
|:---|:---|
| Chrome | 90+ |
| Firefox | 88+ |
| Safari | 14+ |
| Edge | 90+ |
| Mobile Chrome | 90+ |
| Mobile Safari | 14+ |

### 4.4 Performance Requirements

| Metrik | Target |
|:---|:---|
| **Upload time** (1 MB) | < 3 detik |
| **Page load** | < 2 detik |
| **Image load** (via CDN) | < 1 detik |
| **API response** | < 500 ms |
| **Uptime** | 99% |

### 4.5 Security Requirements

| Aspek | Implementasi |
|:---|:---|
| **Password hashing** | bcrypt (cost 12) |
| **Session** | `httponly`, `secure`, `samesite=Lax` |
| **CSRF protection** | Token di setiap form |
| **SQL injection** | Prepared statements (PDO) |
| **XSS** | `htmlspecialchars()` untuk output |
| **File upload** | Validasi MIME type + extension |
| **API key** | 64 karakter random |
| **Rate limiting** | 100 upload/jam per IP |

### 4.6 Storage Requirements

| Item | Limit |
|:---|:---|
| **Max file size** | 5 MB |
| **Max files per upload** | 5 file |
| **Storage per user** | 100 MB (default) |
| **Allowed formats** | JPG, JPEG, PNG, GIF, WebP |
| **Thumbnail size** | 300x300 (max) |

---

## 5. Architecture Overview

### 5.1 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  User Browser                                                │
│  ─ Tailwind CSS, FileBokz, Toastify, Lucide                 │
└──────────────────────┬──────────────────────────────────────┘
                       │ HTTPS
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  Cloudflare Edge (CDN + SSL)                                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│  Server 2 (192.168.1.48)                                    │
│  ─ Caddy + PHP-FPM                                          │
│  ─ Frontend + Backend logic                                 │
└──────────┬────────────────────────────────┬─────────────────┘
           │                                │
           │ PDO (MariaDB)                  │ S3 API (RustFS)
           ▼                                ▼
┌──────────────────────┐     ┌──────────────────────────────┐
│  Server 3 (MariaDB)  │     │  Server 3 (RustFS)           │
│  ─ users             │     │  ─ Bucket: pixicdn           │
│  ─ images            │     │  ─ File gambar + thumbnail   │
│  ─ password_resets   │     │                              │
└──────────────────────┘     └──────────────────────────────┘
```

### 5.2 Data Flow — Upload

```
1. User pilih gambar (FileBokz)
2. POST /upload.php (multipart/form-data)
3. PHP validasi (tipe, ukuran, dll)
4. PHP generate nama unik: 20261010_abc123.jpg
5. PHP upload ke RustFS via AWS SDK
6. RustFS simpan di bucket "pixicdn"
7. PHP generate thumbnail (opsional)
8. PHP simpan metadata ke MariaDB
9. PHP return URL ke user
10. Toastify tampilkan notifikasi sukses
```

### 5.3 Data Flow — Akses Gambar

```
1. User akses https://pixicdn.erdtreeproject.my.id/abc123.jpg
2. Cloudflare Edge (cache jika ada)
3. Cloudflare Tunnel → Server 3 (RustFS :9000)
4. RustFS serve gambar
5. Gambar ditampilkan
6. View counter di-increment (async)
```

---

## 6. Database Schema

### 6.1 Tabel `users`

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    recovery_code_hash VARCHAR(255) NULL,
    api_key VARCHAR(64) UNIQUE NULL,
    storage_quota BIGINT DEFAULT 104857600,
    storage_used BIGINT DEFAULT 0,
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_api_key (api_key)
);
```

### 6.2 Tabel `images`

```sql
CREATE TABLE images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    s3_key VARCHAR(500) NOT NULL,
    public_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) NULL,
    mime_type VARCHAR(50),
    size INT,
    width INT,
    height INT,
    views INT DEFAULT 0,
    ip_address VARCHAR(45),
    is_public BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_filename (filename),
    INDEX idx_user (user_id),
    INDEX idx_uploaded (uploaded_at)
);
```

### 6.3 Tabel `password_resets`

```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user (user_id)
);
```

### 6.4 Tabel `view_logs`

```sql
CREATE TABLE view_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    INDEX idx_image (image_id),
    INDEX idx_viewed (viewed_at)
);
```

---

## 7. UI/UX Design Principles

### 7.1 Design System

| Aspek | Nilai |
|:---|:---|
| **Tema** | Dark mode (default), Light mode (opsional) |
| **Warna Utama** | Indigo (#667eea) → Purple (#764ba2) gradient |
| **Warna Background** | Slate-950 (#0a0e27) |
| **Font** | Inter atau system-ui |
| **Border Radius** | 8px (small), 12px (medium), 16px (large) |
| **Spacing** | 4px, 8px, 16px, 24px, 32px, 48px |

### 7.2 Component Library

| Komponen | Library |
|:---|:---|
| **Button** | Tailwind |
| **Input** | Tailwind |
| **Card** | Tailwind |
| **Modal** | SweetAlert2 |
| **Toast** | Toastify (zzev) |
| **Upload Zone** | FileBokz |
| **Icon** | Lucide |
| **Chart** | Chart.js |

### 7.3 Responsive Breakpoints

| Breakpoint | Width | Layout |
|:---|:---|:---|
| **Mobile** | < 640px | 1 kolom |
| **Tablet** | 640-1024px | 2 kolom |
| **Desktop** | > 1024px | 3-4 kolom |

### 7.4 Accessibility

- Kontras warna WCAG AA
- Keyboard navigation
- ARIA labels
- Alt text untuk gambar
- Focus indicators

---

## 8. Success Metrics

### 8.1 Technical Metrics

| Metrik | Target | Cara Ukur |
|:---|:---|:---|
| **Uptime** | 99% | Uptime Kuma |
| **Upload success rate** | 99% | Log PHP |
| **Average upload time** | < 3 detik | Timer PHP |
| **API response time** | < 500 ms | Log API |
| **Error rate** | < 1% | Log error |

### 8.2 User Metrics

| Metrik | Target |
|:---|:---|
| **Total users** | 10+ (target UTS) |
| **Total images** | 100+ (target UTS) |
| **Total views** | 1000+ (target UTS) |
| **User retention** | 50% (login kembali) |

### 8.3 Business Metrics

| Metrik | Target |
|:---|:---|
| **Demo UTS** | Berjalan lancar |
| **Nilai UTS** | A |
| **Portfolio value** | Bisa dipamerkan |

---

## 9. Timeline & Milestones

### 9.1 Development Timeline

| Hari | Fokus | Deliverable |
|:---|:---|:---|
| **Hari 1** | Setup | Database, tabel, Composer, config |
| **Hari 2** | Auth | Register, login, logout, session |
| **Hari 3** | Upload | Upload form, handler, gallery |
| **Hari 4** | Akun | Profile, ubah password, forgot password |
| **Hari 5** | Polish | Dashboard, API, testing, screenshot |
| **Hari 6** | Dokumentasi | Laporan, PPT, video demo |
| **Hari 7** | Buffer | Perbaikan, latihan demo |

### 9.2 Milestones

| Milestone | Target | Status |
|:---|:---|:---|
| **M1: Database Ready** | Hari 1 | ⏳ |
| **M2: Auth Working** | Hari 2 | ⏳ |
| **M3: Upload Working** | Hari 3 | ⏳ |
| **M4: Account Management** | Hari 4 | ⏳ |
| **M5: MVP Complete** | Hari 5 | ⏳ |
| **M6: Demo Ready** | Hari 7 | ⏳ |

---

## 10. Risks & Mitigation

| # | Risiko | Probabilitas | Dampak | Mitigasi |
|:---|:---|:---|:---|:---|
| 1 | **Storage penuh** | Sedang | Tinggi | Monitor via Beszel, limit 100 MB/user |
| 2 | **RustFS down** | Rendah | Tinggi | Monitoring + alert |
| 3 | **Upload gagal** | Sedang | Sedang | Retry logic + toast error |
| 4 | **Session hijacking** | Rendah | Tinggi | Secure cookies + regenerate |
| 5 | **SQL injection** | Rendah | Tinggi | Prepared statements |
| 6 | **Waktu tidak cukup** | Sedang | Tinggi | Fokus MVP dulu |
| 7 | **Bug saat demo** | Sedang | Tinggi | Video cadangan + latihan |

---

## 11. Open Questions

| # | Pertanyaan | Perlu Keputusan |
|:---|:---|:---|
| 1 | **Cara reset password tanpa email?** | Recovery code? Security question? |
| 2 | **Apakah perlu admin panel?** | Untuk moderate gambar? |
| 3 | **Apakah perlu auto-delete gambar lama?** | Setelah X hari? |
| 4 | **Apakah perlu watermark?** | Untuk gambar tertentu? |
| 5 | **Apakah perlu multi-bahasa?** | ID + EN? |

---

## 12. Appendix

### 12.1 Glossary

| Istilah | Definisi |
|:---|:---|
| **CDN** | Content Delivery Network |
| **SaaS** | Software as a Service |
| **Direct Link** | URL langsung ke file gambar |
| **BBCode** | Format markup untuk forum |
| **Thumbnail** | Versi kecil dari gambar |
| **API Key** | Kunci untuk akses API |

### 12.2 References

- [Tailwind CSS v4 Docs](https://tailwindcss.com/docs)
- [FileBokz GitHub](https://github.com/fivefifteen/filebokz)
- [Toastify (zzev) GitHub](https://github.com/zzev/toastify)
- [Lucide Icons](https://lucide.dev/)
- [AWS SDK for PHP](https://docs.aws.amazon.com/sdk-for-php/)
- [OWASP Password Storage](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)

---

## 🎯 Kesimpulan

PRD ini mencakup:
- ✅ **12 section** lengkap
- ✅ **14 fitur** terdefinisi dengan acceptance criteria
- ✅ **Tech stack** dan library final
- ✅ **Database schema** lengkap
- ✅ **UI/UX principles**
- ✅ **Timeline** 7 hari
- ✅ **Risk mitigation**

**Yang perlu Anda putuskan:**
1. **Cara reset password** tanpa email (recovery code / security question / lain)
2. **Apakah perlu admin panel**
3. **Apakah perlu auto-delete** gambar lama
4. **Apakah perlu watermark**

---

