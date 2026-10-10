
# 📋 Rules Document — PixiCDN

**Versi:** 1.0
**Tanggal:** 10 Oktober 2026
**Status:** Final untuk Implementasi
**Author:** Raka Restu Saputra

---

## 📋 Daftar Isi

1. [Prinsip Umum](#1-prinsip-umum)
2. [Coding Standards (PHP)](#2-coding-standards-php)
3. [Coding Standards (Frontend)](#3-coding-standards-frontend)
4. [Security Rules](#4-security-rules)
5. [Database Rules](#5-database-rules)
6. [File & Folder Rules](#6-file--folder-rules)
7. [API Rules](#7-api-rules)
8. [UI/UX Rules](#8-uiux-rules)
9. [Error Handling](#9-error-handling)
10. [Logging Rules](#10-logging-rules)
11. [Performance Rules](#11-performance-rules)
12. [Git & Version Control](#12-git--version-control)
13. [Deployment Rules](#13-deployment-rules)
14. [Testing Rules](#14-testing-rules)
15. [Documentation Rules](#15-documentation-rules)

---

## 1. Prinsip Umum

### 1.1 Core Principles

| # | Prinsip | Penerapan |
|:---|:---|:---|
| 1 | **Security First** | Setiap fitur harus aman by default |
| 2 | **Simplicity** | Kode simpel lebih baik dari kompleks |
| 3 | **Readability** | Kode dibaca 10x lebih sering dari ditulis |
| 4 | **Consistency** | Ikuti standar yang sudah ditetapkan |
| 5 | **DRY (Don't Repeat Yourself)** | Hindari duplikasi kode |
| 6 | **KISS (Keep It Simple)** | Jangan over-engineer |
| 7 | **YAGNI (You Aren't Gonna Need It)** | Jangan buat fitur yang tidak perlu |

### 1.2 Language Rules

| Aturan | Detail |
|:---|:---|
| **Bahasa Kode** | Bahasa Inggris (variabel, fungsi, komentar) |
| **Bahasa UI** | Bahasa Indonesia |
| **Bahasa Komentar** | Bahasa Indonesia (untuk dokumentasi) |
| **Bahasa Error Message** | Bahasa Indonesia (untuk user) |

### 1.3 File Naming

| Tipe | Format | Contoh |
|:---|:---|:---|
| **PHP file** | `snake_case.php` | `upload_image.php` |
| **JS file** | `kebab-case.js` | `upload-handler.js` |
| **CSS file** | `kebab-case.css` | `main-style.css` |
| **Markdown** | `UPPERCASE.md` | `README.md` |
| **Image** | `kebab-case.ext` | `logo-pixicdn.svg` |
| **Config** | `lowercase.ext` | `config.php` |

---

## 2. Coding Standards (PHP)

### 2.1 PHP Version & Requirements

```php
// Minimal PHP version
PHP >= 8.2

// Wajib aktif
- PDO
- mbstring
- gd
- fileinfo
- openssl
- json
```

### 2.2 File Header

Setiap file PHP **wajib** memiliki header dokumentasi:

```php
<?php
/**
 * ============================================================
 * File: upload.php
 * Deskripsi: Handler untuk upload gambar ke RustFS
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
```

### 2.3 Strict Types

**WAJIB** menambahkan `declare(strict_types=1);` setelah `<?php`:

```php
<?php
declare(strict_types=1);

// Kode selanjutnya
```

### 2.4 Naming Convention

| Tipe | Format | Contoh |
|:---|:---|:---|
| **Variable** | `snake_case` | `$user_id`, `$file_name` |
| **Function** | `snake_case` | `get_user_by_id()`, `validate_email()` |
| **Class** | `PascalCase` | `UserManager`, `ImageUploader` |
| **Constant** | `UPPER_SNAKE` | `MAX_FILE_SIZE`, `DB_HOST` |
| **Boolean** | `is_`, `has_`, `can_` | `$is_active`, `$has_permission` |

**Contoh:**

```php
// ✅ BENAR
$user_id = 123;
$is_logged_in = true;
function get_user_by_id($id) { }
const MAX_FILE_SIZE = 5242880;

// ❌ SALAH
$userId = 123;
$loggedIn = true;
function getUserById($id) { }
const maxFileSize = 5242880;
```

### 2.5 Indentation & Spacing

```php
// ✅ BENAR: 4 spaces, tidak pakai tab
if ($condition) {
    $result = do_something();
}

// ✅ BENAR: 1 spasi setelah koma
function foo($a, $b, $c) { }

// ✅ BENAR: 1 spasi sebelum & sesudah operator
$total = $price + $tax;

// ❌ SALAH
if($condition){
$result=do_something();
}
```

### 2.6 Control Structures

```php
// ✅ BENAR: Kurung kurawal di baris baru untuk function
function example()
{
    if ($condition) {
        // ...
    } elseif ($other) {
        // ...
    } else {
        // ...
    }
}

// ✅ BENAR: Alternatif syntax untuk template
<?php if ($condition): ?>
    <p>Content</p>
<?php endif; ?>

// ❌ SALAH: Kurung kurawal di baris sama untuk function
function example() {
    // ...
}
```

### 2.7 Comments

```php
// ✅ Single-line comment (untuk penjelasan singkat)
$total = $price * $quantity;  // Hitung total harga

// ✅ Multi-line comment (untuk dokumentasi)
/**
 * Validasi file upload
 * 
 * @param array $file File dari $_FILES
 * @return bool True jika valid
 */
function validate_upload($file) { }

// ✅ PHPDoc untuk class & method
/**
 * Class untuk mengelola user
 * 
 * @package PixiCDN
 */
class UserManager
{
    /**
     * Get user by username
     * 
     * @param string $username Username user
     * @return array|null Data user atau null
     */
    public function getByUsername(string $username): ?array
    {
        // ...
    }
}

// ❌ SALAH: Komentar yang tidak berguna
$x = 5;  // Set x to 5
```

### 2.8 String Handling

```php
// ✅ BENAR: Double quote untuk interpolasi
$message = "Hello, $username!";

// ✅ BENAR: Single quote untuk string literal
$path = '/opt/caddy/html';

// ✅ BENAR: Heredoc untuk string multi-baris
$html = <<<HTML
<div class="card">
    <h2>$title</h2>
    <p>$content</p>
</div>
HTML;

// ❌ SALAH: Concatenation berlebihan
$message = 'Hello, ' . $username . '!';
```

### 2.9 Array

```php
// ✅ BENAR: Short array syntax
$items = [1, 2, 3];
$user = [
    'id' => 1,
    'username' => 'raka',
];

// ✅ BENAR: Trailing comma di array multi-baris
$config = [
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'pixicdn',
];

// ❌ SALAH: array() syntax
$items = array(1, 2, 3);
```

### 2.10 Error Handling

```php
// ✅ BENAR: Try-catch dengan error logging
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    jsonResponse(['error' => 'Terjadi kesalahan'], 500);
}

// ❌ SALAH: Silent error
$user = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();
```

### 2.11 Type Declaration

```php
// ✅ BENAR: Type hints untuk parameter & return
function calculate_total(float $price, int $quantity): float
{
    return $price * $quantity;
}

// ✅ BENAR: Nullable type
function find_user(int $id): ?array
{
    // ...
}

// ✅ BENAR: Union type (PHP 8.0+)
function format_value(int|string $value): string
{
    // ...
}

// ❌ SALAH: Tanpa type hints
function calculate_total($price, $quantity)
{
    return $price * $quantity;
}
```

---

## 3. Coding Standards (Frontend)

### 3.1 HTML Rules

```html
<!-- ✅ BENAR: Semantic HTML5 -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PixiCDN - Image Hosting</title>
</head>
<body>
    <header>
        <nav>...</nav>
    </header>
    <main>
        <section>
            <h1>Judul</h1>
        </section>
    </main>
    <footer>...</footer>
</body>
</html>

<!-- ❌ SALAH: Div soup -->
<div class="header">
    <div class="nav">...</div>
</div>
<div class="main">
    <div class="section">
        <div class="title">Judul</div>
    </div>
</div>
```

**Aturan HTML:**

| Aturan | Detail |
|:---|:---|
| **Indentasi** | 4 spasi |
| **Attribute** | Pakai double quote |
| **Self-closing** | `<img />`, `<br />`, `<input />` |
| **Alt text** | Wajib untuk semua `<img>` |
| **ARIA labels** | Wajib untuk elemen interaktif |
| **Lang attribute** | Wajib di `<html>` |

### 3.2 CSS Rules (Tailwind)

```html
<!-- ✅ BENAR: Utility classes Tailwind -->
<div class="flex items-center justify-between p-4 bg-slate-900 rounded-lg">
    <h2 class="text-xl font-bold text-white">Title</h2>
    <button class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-500">
        Click
    </button>
</div>

<!-- ✅ BENAR: Custom CSS untuk komponen kompleks -->
<style>
    .custom-gradient {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
</style>

<!-- ❌ SALAH: Inline style berlebihan -->
<div style="display: flex; align-items: center; padding: 16px; background: #0a0e27;">
```

**Aturan CSS:**

| Aturan | Detail |
|:---|:---|
| **Prioritas** | Tailwind utility > Custom CSS |
| **Custom CSS** | Hanya untuk animasi kompleks atau gradient |
| **Naming** | `kebab-case` untuk custom class |
| **Warna** | Pakai Tailwind color palette |

### 3.3 JavaScript Rules

```javascript
// ✅ BENAR: ES6+ syntax
const user = { id: 1, name: 'Raka' };
let counter = 0;

// ✅ BENAR: Arrow function
const add = (a, b) => a + b;

// ✅ BENAR: Async/await
async function uploadFile(file) {
    try {
        const response = await fetch('/upload.php', {
            method: 'POST',
            body: file
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Upload failed:', error);
        throw error;
    }
}

// ✅ BENAR: Template literal
const message = `Hello, ${user.name}!`;

// ✅ BENAR: Destructuring
const { id, name } = user;

// ❌ SALAH: var (gunakan const/let)
var counter = 0;

// ❌ SALAH: Callback hell
fetch('/upload.php', function(response) {
    response.json().then(function(data) {
        console.log(data);
    });
});
```

**Aturan JavaScript:**

| Aturan | Detail |
|:---|:---|
| **Variable** | `const` default, `let` jika perlu diubah |
| **Function** | Arrow function untuk callback |
| **Async** | `async/await` > Promise chain |
| **String** | Template literal untuk interpolasi |
| **Semicolon** | Wajib di akhir statement |
| **Console** | `console.log` hanya untuk debugging, hapus sebelum commit |

### 3.4 Library Usage

```html
<!-- ✅ BENAR: Load library dari CDN dengan versi spesifik -->
<script src="https://cdn.jsdelivr.net/npm/@zzev/toastify@1.0.0/dist/toastify.min.js"></script>

<!-- ❌ SALAH: Tanpa versi -->
<script src="https://cdn.jsdelivr.net/npm/@zzev/toastify/dist/toastify.min.js"></script>
```

**Aturan Library:**

| Library | Fungsi | Aturan |
|:---|:---|:---|
| **Tailwind CSS** | Styling | Selalu load di `<head>` |
| **Lucide** | Icons | Panggil `lucide.createIcons()` setelah DOM ready |
| **Toastify** | Toast | Wrap dalam fungsi `showToast()` |
| **FileBokz** | Upload | Init setelah DOM ready |
| **Chart.js** | Charts | Init dengan `canvas` element |
| **SweetAlert2** | Modal | Pakai untuk konfirmasi delete |

---

## 4. Security Rules

### 4.1 Password & Authentication

**WAJIB:**

```php
// ✅ Hash password dengan bcrypt cost 12
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ✅ Verify password
if (password_verify($password, $hash)) {
    // Login sukses
}

// ✅ Regenerate session ID setelah login
session_regenerate_id(true);

// ✅ Set session cookie flags
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
```

**DILARANG:**

```php
// ❌ MD5 / SHA-1 untuk password
$hash = md5($password);
$hash = sha1($password);

// ❌ Plain text password
$password = $_POST['password'];

// ❌ Session tanpa regenerate
session_start();
$_SESSION['user_id'] = $user_id;
```

### 4.2 SQL Injection Prevention

**WAJIB:**

```php
// ✅ Prepared statements (PDO)
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// ✅ Named parameters
$stmt = $pdo->prepare("SELECT * FROM images WHERE user_id = :user_id AND id = :id");
$stmt->execute(['user_id' => $user_id, 'id' => $image_id]);
```

**DILARANG:**

```php
// ❌ Query dengan string concatenation
$query = "SELECT * FROM users WHERE username = '$username'";
$result = $pdo->query($query);

// ❌ Query dengan variable interpolasi
$pdo->query("SELECT * FROM images WHERE id = $id");
```

### 4.3 XSS Prevention

**WAJIB:**

```php
// ✅ Escape output HTML
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// ✅ Escape untuk atribut
<input value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">

// ✅ Gunakan fungsi sanitize() dari helpers
echo sanitize($user_input);
```

**DILARANG:**

```php
// ❌ Output langsung tanpa escape
echo $user_input;
echo $_GET['name'];

// ❌ InnerHTML tanpa sanitasi
document.getElementById('output').innerHTML = userInput;
```

### 4.4 CSRF Protection

**WAJIB:**

```php
// ✅ Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ✅ Hidden input di form
<input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

// ✅ Verify CSRF di handler
if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    die('CSRF token invalid');
}
```

### 4.5 File Upload Security

**WAJIB:**

```php
// ✅ Whitelist ekstensi
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    die('Ekstensi tidak diizinkan');
}

// ✅ Validasi MIME type dengan getimagesize
$imageInfo = getimagesize($_FILES['file']['tmp_name']);
if ($imageInfo === false) {
    die('File bukan gambar valid');
}

// ✅ Validasi ukuran
if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
    die('File terlalu besar');
}

// ✅ Generate nama file acak
$filename = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
```

**DILARANG:**

```php
// ❌ Percaya pada $_FILES['file']['type']
if ($_FILES['file']['type'] === 'image/jpeg') {
    // Bisa di-spoof!
}

// ❌ Simpan dengan nama asli
move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);

// ❌ Simpan di folder yang bisa diakses langsung
move_uploaded_file($_FILES['file']['tmp_name'], '/var/www/html/uploads/');
```

### 4.6 Session Security

```php
// ✅ Config session di awal
session_name('PIXICDN_SESSION');
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'secure' => true,        // HTTPS only
    'httponly' => true,      // No JavaScript access
    'samesite' => 'Lax',     // CSRF protection
]);

// ✅ Regenerate setelah login
session_regenerate_id(true);

// ✅ Destroy session saat logout
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
```

### 4.7 Rate Limiting

```php
// ✅ Cek rate limit sebelum proses
function check_rate_limit(string $identifier, string $action, int $max, int $windowSeconds = 3600): bool
{
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT request_count, window_end
        FROM rate_limits
        WHERE identifier = ? AND action = ?
    ");
    $stmt->execute([$identifier, $action]);
    $row = $stmt->fetch();

    if (!$row || strtotime($row['window_end']) < time()) {
        // Reset window
        $stmt = $pdo->prepare("
            INSERT INTO rate_limits (identifier, action, request_count, window_start, window_end)
            VALUES (?, ?, 1, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
            ON DUPLICATE KEY UPDATE
                request_count = 1,
                window_start = NOW(),
                window_end = DATE_ADD(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $action, $windowSeconds, $windowSeconds]);
        return true;
    }

    if ($row['request_count'] >= $max) {
        return false;
    }

    $pdo->prepare("UPDATE rate_limits SET request_count = request_count + 1 WHERE identifier = ? AND action = ?")
        ->execute([$identifier, $action]);
    return true;
}
```

### 4.8 Authorization

```php
// ✅ Cek ownership sebelum akses
function require_ownership(int $image_id, int $user_id): void
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT user_id FROM images WHERE id = ? AND is_deleted = FALSE");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();

    if (!$image || $image['user_id'] !== $user_id) {
        http_response_code(403);
        die('Akses ditolak');
    }
}
```

### 4.9 API Key Security

```php
// ✅ Generate API key dengan CSPRNG
$api_key = bin2hex(random_bytes(32));  // 64 karakter

// ✅ Validasi API key di setiap request
function validate_api_key(string $api_key): ?array
{
    if (empty($api_key)) return null;

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE api_key = ? AND is_active = TRUE");
    $stmt->execute([$api_key]);
    return $stmt->fetch() ?: null;
}
```

### 4.10 Security Checklist

| # | Item | Status |
|:---|:---|:---|
| 1 | Password di-hash dengan bcrypt cost 12 | ⏳ |
| 2 | Session cookie httponly + secure + samesite | ⏳ |
| 3 | Session regenerate setelah login | ⏳ |
| 4 | Prepared statements untuk semua query | ⏳ |
| 5 | Output escape dengan htmlspecialchars | ⏳ |
| 6 | CSRF token di setiap form | ⏳ |
| 7 | Validasi file upload (ext + MIME + size) | ⏳ |
| 8 | Nama file upload di-generate random | ⏳ |
| 9 | Rate limiting untuk upload & login | ⏳ |
| 10 | Ownership check untuk akses gambar | ⏳ |
| 11 | API key 64 karakter CSPRNG | ⏳ |
| 12 | Config file di luar document root | ⏳ |
| 13 | Error log tidak ditampilkan ke user | ⏳ |
| 14 | HTTPS only (via Cloudflare) | ⏳ |

---

## 5. Database Rules

### 5.1 Naming Convention

| Tipe | Format | Contoh |
|:---|:---|:---|
| **Table** | `snake_case` plural | `users`, `images`, `password_resets` |
| **Column** | `snake_case` | `user_id`, `created_at` |
| **Primary key** | `id` | `id` |
| **Foreign key** | `{table}_id` | `user_id`, `image_id` |
| **Index** | `idx_{column}` | `idx_username` |
| **Unique key** | `uk_{column}` | `uk_username` |
| **Foreign key constraint** | `fk_{table}_{ref}` | `fk_images_user` |

### 5.2 Data Types

| Data | Tipe | Alasan |
|:---|:---|:---|
| **ID** | `INT UNSIGNED` | Cukup untuk miliaran row |
| **Large count** | `BIGINT UNSIGNED` | View logs, API logs |
| **String pendek** | `VARCHAR(50-255)` | Username, email |
| **String panjang** | `TEXT` | Bio, deskripsi |
| **Boolean** | `BOOLEAN` / `TINYINT(1)` | Flag |
| **Timestamp** | `TIMESTAMP` | `created_at`, `updated_at` |
| **Enum** | `ENUM(...)` | Status, role |

### 5.3 Standard Columns

Setiap tabel **wajib** memiliki:

```sql
id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

### 5.4 Foreign Key Rules

```sql
-- ✅ ON DELETE CASCADE: jika parent dihapus, child ikut terhapus
-- Contoh: password_resets → users
CONSTRAINT fk_resets_user 
    FOREIGN KEY (user_id) REFERENCES users(id) 
    ON DELETE CASCADE ON UPDATE CASCADE

-- ✅ ON DELETE SET NULL: jika parent dihapus, child jadi NULL
-- Contoh: images → users (gambar tetap ada meski user dihapus)
CONSTRAINT fk_images_user 
    FOREIGN KEY (user_id) REFERENCES users(id) 
    ON DELETE SET NULL ON UPDATE CASCADE
```

**Kapan pakai CASCADE vs SET NULL:**

| Tipe | Kapan Pakai |
|:---|:---|
| **CASCADE** | Child tidak berarti tanpa parent (password_resets) |
| **SET NULL** | Child masih berarti tanpa parent (images anonymous) |

### 5.5 Index Rules

**WAJIB ada index untuk:**

| Kolom | Alasan |
|:---|:---|
| `username` | Sering di-query untuk login |
| `api_key` | Di-query setiap API request |
| `email` | Untuk pencarian user |
| `created_at` | Untuk sorting |
| `user_id` (FK) | Untuk query by user |
| `uploaded_at` | Untuk gallery sorting |

**Format:**

```sql
INDEX idx_username (username),
INDEX idx_user_uploaded (user_id, uploaded_at)  -- Composite
```

### 5.6 Query Rules

**WAJIB:**

```php
// ✅ Prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// ✅ LIMIT untuk query yang bisa besar
$stmt = $pdo->prepare("SELECT * FROM images ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);

// ✅ Alias kolom untuk readability
$stmt = $pdo->query("SELECT u.username, i.filename FROM users u JOIN images i ON u.id = i.user_id");
```

**DILARANG:**

```php
// ❌ SELECT * untuk tabel besar
$pdo->query("SELECT * FROM view_logs");  // Bisa jutaan row!

// ❌ Query tanpa WHERE untuk UPDATE/DELETE
$pdo->query("UPDATE users SET is_active = 1");  // Semua user!
$pdo->query("DELETE FROM images");               // Semua gambar!
```

### 5.7 Migration Rules

```sql
-- ✅ File naming: 001_description.sql
-- ✅ Setiap migration idempotent (bisa dijalankan berulang)
CREATE TABLE IF NOT EXISTS users (...);
CREATE INDEX IF NOT EXISTS idx_username ON users(username);

-- ✅ Foreign key checks di awal
SET FOREIGN_KEY_CHECKS = 0;
-- ... create tables ...
SET FOREIGN_KEY_CHECKS = 1;

-- ✅ Comment di awal file
-- ============================================================
-- Migration: 001_initial_schema
-- Date: 2026-10-10
-- Description: Create initial tables
-- ============================================================
```

### 5.8 Backup Rules

| Aturan | Detail |
|:---|:---|
| **Frekuensi** | Harian untuk production |
| **Retensi** | 7 hari untuk daily, 4 minggu untuk weekly |
| **Lokasi** | `/opt/backup/mariadb/` |
| **Format** | `.sql` (mysqldump) |
| **Nama** | `all-db_YYYYMMDD_HHMMSS.sql` |
| **Enkripsi** | Opsional untuk production |

---

## 6. File & Folder Rules

### 6.1 Document Root

```
/opt/caddy/html/pixicdn/     ← Document root
├── index.php                ← Bisa diakses langsung
├── upload.php               ← Bisa diakses langsung
├── config.php               ← ⚠️ TIDAK BOLEH diakses langsung
├── vendor/                  ← ⚠️ TIDAK BOLEH diakses langsung
├── logs/                    ← ⚠️ TIDAK BOLEH diakses langsung
└── migrations/              ← ⚠️ TIDAK BOLEH diakses langsung
```

### 6.2 Protect Sensitive Files

**Caddyfile:**

```caddy
# Blokir akses ke file sensitif
@blocked {
    path /config.php /vendor/* /logs/* /migrations/* *.sql
}
respond @blocked 403
```

**Alternative dengan PHP:**

```php
// Di awal config.php
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    http_response_code(403);
    die('Akses ditolak');
}
```

### 6.3 Folder Structure Rules

```
pixicdn/
├── assets/                 # Static assets (bisa diakses publik)
│   ├── css/
│   ├── js/
│   └── img/
│
├── auth/                   # Authentication pages
│   └── *.php
│
├── dashboard/              # User dashboard
│   └── *.php
│
├── api/                    # REST API endpoints
│   └── *.php
│
├── includes/               # Shared includes (PHP)
│   └── *.php
│
├── logs/                   # Application logs (private)
│   └── *.log
│
├── migrations/             # Database migrations (private)
│   └── *.sql
│
└── vendor/                 # Composer packages (private)
    └── ...
```

### 6.4 Permission Rules

| Item | Permission | Owner |
|:---|:---|:---|
| **Folder** | `755` | `1000:1000` |
| **File PHP** | `644` | `1000:1000` |
| **File config** | `640` | `1000:1000` |
| **File log** | `640` | `1000:1000` |
| **Upload folder** | `755` | `1000:82` |

**Set permission:**

```bash
# Semua folder
find /opt/caddy/html/pixicdn -type d -exec chmod 755 {} \;

# Semua file
find /opt/caddy/html/pixicdn -type f -exec chmod 644 {} \;

# Config & log (khusus)
chmod 640 /opt/caddy/html/pixicdn/config.php
chmod 640 /opt/caddy/html/pixicdn/logs/*.log

# Owner
chown -R 1000:1000 /opt/caddy/html/pixicdn
```

### 6.5 .gitignore Rules

```gitignore
# File yang TIDAK boleh di-commit
.env
config.php                    # Berisi kredensial
logs/*.log
logs/*.txt
vendor/                       # Composer packages
uploads/                      # User uploads
migrations/*.local.sql
*.db
*.sqlite

# OS files
.DS_Store
Thumbs.db

# IDE files
.vscode/
.idea/
*.swp

# Backup files
*.bak
*.backup
*.old
```

---

## 7. API Rules

### 7.1 Endpoint Naming

| Tipe | Format | Contoh |
|:---|:---|:---|
| **Resource** | `/api/{resource}` | `/api/images` |
| **Resource by ID** | `/api/{resource}/{id}` | `/api/images/123` |
| **Sub-resource** | `/api/{resource}/{id}/{sub}` | `/api/users/1/images` |
| **Action** | `/api/{resource}/{id}/{action}` | `/api/images/123/delete` |

### 7.2 HTTP Methods

| Method | Fungsi | Contoh |
|:---|:---|:---|
| **GET** | Baca data | `GET /api/images` |
| **POST** | Buat data baru | `POST /api/upload` |
| **PUT** | Update data (full) | `PUT /api/images/123` |
| **PATCH** | Update data (partial) | `PATCH /api/images/123` |
| **DELETE** | Hapus data | `DELETE /api/images/123` |

### 7.3 Response Format

**Success:**

```json
{
    "success": true,
    "data": {
        "id": 123,
        "url": "https://pixicdn.erdtreeproject.my.id/abc123.jpg",
        "size": 245760,
        "views": 0
    },
    "meta": {
        "timestamp": "2026-10-10T14:00:00+07:00",
        "request_id": "req_abc123"
    }
}
```

**Error:**

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Ukuran file melebihi batas",
        "details": {
            "field": "file",
            "max_size": 5242880
        }
    },
    "meta": {
        "timestamp": "2026-10-10T14:00:00+07:00",
        "request_id": "req_abc123"
    }
}
```

### 7.4 HTTP Status Codes

| Code | Kapan Pakai |
|:---|:---|
| **200** | Success (GET, PUT, PATCH) |
| **201** | Created (POST) |
| **204** | No Content (DELETE) |
| **400** | Bad Request (validasi gagal) |
| **401** | Unauthorized (API key invalid) |
| **403** | Forbidden (tidak punya akses) |
| **404** | Not Found (resource tidak ada) |
| **422** | Unprocessable Entity (validasi semantic) |
| **429** | Too Many Requests (rate limit) |
| **500** | Internal Server Error |

### 7.5 Authentication

```php
// ✅ API key di header
GET /api/images
Header: X-API-Key: a3f8c2...

// ✅ Validasi API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
$user = validate_api_key($api_key);
if (!$user) {
    jsonResponse(['error' => 'Invalid API key'], 401);
}
```

### 7.6 Rate Limiting

| Endpoint | Limit |
|:---|:---|
| `POST /api/upload` | 100/jam |
| `GET /api/images` | 1000/jam |
| `DELETE /api/images/{id}` | 100/jam |

### 7.7 Pagination

```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "page": 1,
        "per_page": 20,
        "total": 145,
        "total_pages": 8,
        "has_next": true,
        "has_prev": false
    }
}
```

---

## 8. UI/UX Rules

### 8.1 Design Consistency

| Aspek | Nilai |
|:---|:---|
| **Tema** | Dark mode (default) |
| **Warna Utama** | Indigo → Purple gradient (`#667eea` → `#764ba2`) |
| **Background** | `#0a0e27` (slate-950) |
| **Text** | White (`#fff`) dengan opacity |
| **Border Radius** | 8px (small), 12px (medium), 16px (large) |
| **Spacing** | Kelipatan 4px (4, 8, 16, 24, 32, 48) |

### 8.2 Component Rules

**Button:**

```html
<!-- ✅ Primary button -->
<button class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 transition">
    Upload
</button>

<!-- ✅ Secondary button -->
<button class="px-6 py-3 bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition">
    Cancel
</button>

<!-- ✅ Danger button -->
<button class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-500 transition">
    Delete
</button>
```

**Card:**

```html
<div class="bg-slate-900 border border-white/10 rounded-xl p-6">
    <!-- Content -->
</div>
```

**Input:**

```html
<input type="text" 
       class="w-full px-4 py-3 bg-slate-800 border border-white/10 rounded-lg 
              text-white focus:outline-none focus:border-indigo-500 transition">
```

### 8.3 Feedback Rules

| Aksi | Feedback | Library |
|:---|:---|:---|
| **Upload sukses** | Toast hijau | Toastify |
| **Upload gagal** | Toast merah | Toastify |
| **Copy link** | Toast + ubah teks tombol | Toastify |
| **Delete** | Modal konfirmasi | SweetAlert2 |
| **Loading** | Spinner di tombol | Tailwind |
| **Form error** | Inline error di bawah input | Tailwind |

### 8.4 Loading States

```html
<!-- ✅ Tombol dengan loading state -->
<button id="uploadBtn" class="px-6 py-3 bg-indigo-600 text-white rounded-lg">
    <span class="btn-text">Upload</span>
    <svg class="btn-loading hidden animate-spin h-5 w-5" viewBox="0 0 24 24">
        <!-- Spinner -->
    </svg>
</button>

<script>
document.getElementById('uploadBtn').addEventListener('click', async function() {
    this.disabled = true;
    this.querySelector('.btn-text').textContent = 'Memproses...';
    this.querySelector('.btn-loading').classList.remove('hidden');
    
    try {
        await uploadFile();
    } finally {
        this.disabled = false;
        this.querySelector('.btn-text').textContent = 'Upload';
        this.querySelector('.btn-loading').classList.add('hidden');
    }
});
</script>
```

### 8.5 Empty States

```html
<!-- ✅ Empty state untuk gallery -->
<div class="text-center py-16">
    <svg class="w-16 h-16 mx-auto text-slate-600 mb-4"><!-- Icon --></svg>
    <h3 class="text-xl font-semibold text-white mb-2">Belum ada gambar</h3>
    <p class="text-slate-400 mb-6">Upload gambar pertama Anda untuk memulai</p>
    <a href="/upload.php" class="px-6 py-3 bg-indigo-600 text-white rounded-lg">
        Upload Sekarang
    </a>
</div>
```

### 8.6 Responsive Rules

| Breakpoint | Width | Layout |
|:---|:---|:---|
| **Mobile** | `< 640px` | 1 kolom, sidebar hidden |
| **Tablet** | `640-1024px` | 2 kolom |
| **Desktop** | `> 1024px` | 3-4 kolom, sidebar visible |

### 8.7 Accessibility Rules

| Aturan | Detail |
|:---|:---|
| **Alt text** | Wajib untuk semua `<img>` |
| **ARIA labels** | Untuk tombol icon-only |
| **Keyboard nav** | Semua elemen interaktif bisa di-tab |
| **Focus indicator** | Visible focus ring |
| **Color contrast** | Minimal WCAG AA (4.5:1) |
| **Form labels** | Setiap input punya `<label>` |

---

## 9. Error Handling

### 9.1 Error Levels

| Level | Kapan Pakai | Log? | Tampil ke User? |
|:---|:---|:---|:---|
| **DEBUG** | Development | ✅ | ✅ (jika APP_DEBUG) |
| **INFO** | Informasi umum | ✅ | ❌ |
| **WARNING** | Potensi masalah | ✅ | ❌ |
| **ERROR** | Error yang ditangani | ✅ | ⚠️ (generic message) |
| **FATAL** | Error yang tidak bisa ditangani | ✅ | ⚠️ (generic message) |

### 9.2 Error Response Rules

**Development (`APP_DEBUG = true`):**

```php
catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
```

**Production (`APP_DEBUG = false`):**

```php
catch (Exception $e) {
    error_log($e->getMessage());  // Log detail
    die("Terjadi kesalahan pada sistem. Silakan coba lagi.");  // Generic
}
```

### 9.3 Error Codes

| Code | Deskripsi |
|:---|:---|
| `AUTH_001` | Invalid credentials |
| `AUTH_002` | Session expired |
| `AUTH_003` | Account disabled |
| `UPLOAD_001` | File too large |
| `UPLOAD_002` | Invalid file type |
| `UPLOAD_003` | Storage quota exceeded |
| `DB_001` | Database connection failed |
| `DB_002` | Query failed |
| `S3_001` | RustFS connection failed |
| `S3_002` | Upload to RustFS failed |
| `RATE_001` | Rate limit exceeded |
| `VALIDATION_001` | Required field missing |
| `VALIDATION_002` | Invalid format |

### 9.4 Custom Exception

```php
class AppException extends Exception
{
    private string $errorCode;
    
    public function __construct(string $message, string $errorCode, int $statusCode = 400)
    {
        parent::__construct($message, $statusCode);
        $this->errorCode = $errorCode;
    }
    
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

// Usage
try {
    if ($fileSize > MAX_FILE_SIZE) {
        throw new AppException('File terlalu besar', 'UPLOAD_001', 400);
    }
} catch (AppException $e) {
    jsonResponse([
        'error' => $e->getErrorCode(),
        'message' => $e->getMessage(),
    ], $e->getCode());
}
```

---

## 10. Logging Rules

### 10.1 Log Levels

```php
// ✅ Log level yang digunakan
ERROR   - Error yang perlu diperbaiki
WARNING - Potensi masalah
INFO    - Kejadian penting (login, upload)
DEBUG   - Detail untuk debugging (dev only)
```

### 10.2 Log Format

```
[2026-10-10 14:00:00] [ERROR] [UPLOAD_001] User 123: File terlalu besar (6.2 MB)
[2026-10-10 14:01:00] [INFO] [AUTH] User 123: Login successful from 192.168.1.1
[2026-10-10 14:02:00] [WARNING] [RATE_LIMIT] IP 192.168.1.1: Rate limit exceeded for upload
```

### 10.3 What to Log

**WAJIB log:**

| Event | Level | Detail |
|:---|:---|:---|
| **Login success** | INFO | User ID, IP, user agent |
| **Login failed** | WARNING | Username, IP |
| **Register** | INFO | User ID, IP |
| **Upload** | INFO | User ID, filename, size |
| **Delete** | INFO | User ID, image ID |
| **Error** | ERROR | Message, trace, context |
| **Rate limit** | WARNING | IP, action |

**DILARANG log:**

| Data | Alasan |
|:---|:---|
| **Password (plain/text)** | Bahaya keamanan |
| **Password hash** | Tidak perlu |
| **API key** | Bahaya keamanan |
| **Credit card** | PCI compliance |
| **Session ID** | Bahaya keamanan |

### 10.4 Log Rotation

```bash
# Cron job untuk rotation
# /etc/logrotate.d/pixicdn

/opt/caddy/html/pixicdn/logs/*.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 640 1000 1000
}
```

---

## 11. Performance Rules

### 11.1 Query Optimization

```php
// ✅ LIMIT untuk semua query yang bisa besar
$stmt = $pdo->prepare("SELECT * FROM images ORDER BY uploaded_at DESC LIMIT 20 OFFSET ?");

// ✅ Index untuk kolom yang sering di-WHERE
// CREATE INDEX idx_user ON images(user_id);

// ✅ JOIN daripada subquery
// ✅ Pilih kolom spesifik daripada SELECT *
```

### 11.2 Caching

| Data | Cache | TTL |
|:---|:---|:---|
| **Image** | Cloudflare Edge | 1 tahun |
| **CSS/JS** | Browser + Cloudflare | 1 tahun |
| **API response** | - | (tidak di-cache) |
| **PHP opcode** | OPcache | 1 jam |

### 11.3 Image Optimization

| Aspek | Target |
|:---|:---|
| **Max file size** | 5 MB |
| **Thumbnail** | 300x300 max |
| **Format** | JPG/PNG/WebP/GIF |
| **Lazy load** | `loading="lazy"` |

### 11.4 Asset Loading

```html
<!-- ✅ Load CSS di head -->
<link rel="stylesheet" href="/assets/css/main.css">

<!-- ✅ Load JS di akhir body -->
<script src="/assets/js/main.js"></script>

<!-- ✅ Preconnect untuk CDN -->
<link rel="preconnect" href="https://cdn.jsdelivr.net">
```

---

## 12. Git & Version Control

### 12.1 Branch Naming

| Branch | Format | Contoh |
|:---|:---|:---|
| **Main** | `main` | `main` |
| **Feature** | `feature/{nama}` | `feature/user-auth` |
| **Bugfix** | `bugfix/{nama}` | `bugfix/upload-error` |
| **Hotfix** | `hotfix/{nama}` | `hotfix/security-patch` |

### 12.2 Commit Message

```
Format: {type}({scope}): {description}

Type:
- feat: fitur baru
- fix: bug fix
- docs: dokumentasi
- style: formatting
- refactor: refactoring
- test: testing
- chore: maintenance

Contoh:
feat(auth): add forgot password feature
fix(upload): resolve file size validation bug
docs(readme): update installation guide
```

### 12.3 Commit Rules

| Aturan | Detail |
|:---|:---|
| **Atomic** | 1 commit = 1 perubahan logis |
| **Frequent** | Commit setiap 30-60 menit |
| **Tested** | Commit hanya kode yang sudah di-test |
| **No secrets** | Jangan commit API key, password |

### 12.4 Pull Request Rules

- Wajib ada deskripsi perubahan
- Wajib ada screenshot (jika UI)
- Wajib lolos testing
- Minimal 1 reviewer (jika tim)

---

## 13. Deployment Rules

### 13.1 Pre-Deployment Checklist

- [ ] Semua test passed
- [ ] Tidak ada `console.log` di production
- [ ] Tidak ada `var_dump` / `print_r`
- [ ] `APP_DEBUG = false`
- [ ] Config kredensial di environment variable
- [ ] Migration sudah siap
- [ ] Backup database terbaru
- [ ] Notifikasi ke user (jika perlu)

### 13.2 Deployment Steps

```bash
# 1. Backup database
/opt/scripts/backup-db.sh

# 2. Pull latest code
cd /opt/caddy/html/pixicdn
sudo git pull origin main

# 3. Install dependencies
sudo composer install --no-dev --optimize-autoloader

# 4. Run migrations
mariadb -h 192.168.1.50 -u pixicdn_user -p pixicdn_db < migrations/002_new_feature.sql

# 5. Set permission
sudo chown -R 1000:1000 .
sudo chmod 640 config.php

# 6. Restart services (jika perlu)
sudo systemctl restart phpfpm.service

# 7. Test
curl -I https://pixicdn.erdtreeproject.my.id
```

### 13.3 Rollback Procedure

```bash
# Jika deployment gagal

# 1. Rollback code
sudo git reset --hard HEAD~1
sudo git pull origin main

# 2. Restore database
mariadb -h 192.168.1.50 -u pixicdn_user -p pixicdn_db < /opt/backup/mariadb/backup_before_deploy.sql

# 3. Restart
sudo systemctl restart phpfpm.service
```

---

## 14. Testing Rules

### 14.1 Manual Testing Checklist

**Upload:**

- [ ] Upload 1 file sukses
- [ ] Upload 5 file sukses
- [ ] Upload file > 5 MB → error
- [ ] Upload file non-image → error
- [ ] Upload tanpa file → error

**Auth:**

- [ ] Register dengan username baru → sukses
- [ ] Register dengan username yang sudah ada → error
- [ ] Login dengan kredensial benar → sukses
- [ ] Login dengan kredensial salah → error
- [ ] Logout → session clear
- [ ] Forgot password dengan recovery code benar → sukses
- [ ] Forgot password dengan recovery code salah → error

**Gallery:**

- [ ] Gallery menampilkan semua gambar
- [ ] Filter bekerja
- [ ] Pagination bekerja
- [ ] Copy link bekerja
- [ ] Delete bekerja

### 14.2 Browser Testing

| Browser | Version | Status |
|:---|:---|:---|
| Chrome | Latest | ⏳ |
| Firefox | Latest | ⏳ |
| Safari | Latest | ⏳ |
| Edge | Latest | ⏳ |
| Mobile Chrome | Latest | ⏳ |

### 14.3 Responsive Testing

| Device | Width | Status |
|:---|:---|:---|
| Mobile | 375px | ⏳ |
| Tablet | 768px | ⏳ |
| Desktop | 1440px | ⏳ |

---

## 15. Documentation Rules

### 15.1 Required Documentation

| File | Deskripsi |
|:---|:---|
| **README.md** | Deskripsi project, instalasi, cara pakai |
| **PRD.md** | Product Requirements Document |
| **ARCHITECTURE.md** | Architecture Document |
| **RULES.md** | Dokumen ini |
| **SCHEMA.md** | Database schema |
| **CHANGELOG.md** | Log perubahan |
| **LICENSE** | Lisensi project |

### 15.2 README Structure

```markdown
# PixiCDN

## Deskripsi
[Deskripsi singkat]

## Fitur
- [Fitur 1]
- [Fitur 2]

## Tech Stack
- PHP 8.2
- MariaDB 11
- RustFS

## Instalasi
[Langkah instalasi]

## Cara Pakai
[Panduan penggunaan]

## Struktur Folder
[Struktur]

## Kontribusi
[Panduan kontribusi]

## Lisensi
[Lisensi]
```

### 15.3 Code Documentation

- **Function:** Wajib ada PHPDoc
- **Class:** Wajib ada PHPDoc
- **Complex logic:** Wajib ada komentar
- **API endpoint:** Wajib ada dokumentasi

---

## 📋 Checklist Implementasi

| # | Item | Status |
|:---|:---|:---|
| **Coding Standards** | | |
| 1 | `declare(strict_types=1)` di setiap PHP | ⏳ |
| 2 | Naming convention (snake_case, PascalCase) | ⏳ |
| 3 | PHPDoc untuk function/class | ⏳ |
| **Security** | | |
| 4 | Bcrypt password hashing | ⏳ |
| 5 | Prepared statements | ⏳ |
| 6 | HTML escaping | ⏳ |
| 7 | CSRF token | ⏳ |
| 8 | File upload validation | ⏳ |
| 9 | Session security | ⏳ |
| 10 | Rate limiting | ⏳ |
| **Database** | | |
| 11 | Foreign key constraints | ⏳ |
| 12 | Indexes pada kolom query | ⏳ |
| 13 | Prepared statements | ⏳ |
| **File & Folder** | | |
| 14 | Permission 755/644 | ⏳ |
| 15 | Config di luar doc root | ⏳ |
| 16 | .gitignore lengkap | ⏳ |
| **API** | | |
| 17 | Response format konsisten | ⏳ |
| 18 | HTTP status codes tepat | ⏳ |
| 19 | API key authentication | ⏳ |
| **UI/UX** | | |
| 20 | Toast untuk feedback | ⏳ |
| 21 | Loading states | ⏳ |
| 22 | Empty states | ⏳ |
| 23 | Responsive | ⏳ |
| **Error Handling** | | |
| 24 | Try-catch untuk semua operasi | ⏳ |
| 25 | Error logging | ⏳ |
| 26 | Generic error untuk user | ⏳ |
| **Git** | | |
| 27 | Commit message format | ⏳ |
| 28 | Tidak commit secrets | ⏳ |
| **Testing** | | |
| 29 | Manual testing checklist | ⏳ |
| 30 | Browser testing | ⏳ |

---

## 🎯 Prinsip Final

| # | Prinsip | Penerapan |
|:---|:---|:---|
| 1 | **Security First** | Semua input divalidasi, semua output di-escape |
| 2 | **Simplicity** | Native PHP, no framework, minimal dependencies |
| 3 | **Consistency** | Naming convention, format seragam |
| 4 | **DRY** | Helper functions untuk kode berulang |
| 5 | **Readability** | Kode mudah dibaca, komentar jelas |
| 6 | **Testability** | Setiap fitur bisa di-test manual |
| 7 | **Maintainability** | Mudah dimodifikasi di masa depan |

---

## ✅ Kesimpulan

Rules Document PixiCDN mencakup:

- ✅ **15 section** lengkap
- ✅ **Coding standards** — PHP & frontend
- ✅ **Security rules** — 10 aspek keamanan
- ✅ **Database rules** — naming, types, query
- ✅ **File & folder rules** — struktur, permission
- ✅ **API rules** — endpoint, format, auth
- ✅ **UI/UX rules** — design, feedback, accessibility
- ✅ **Error handling** — level, format, codes
- ✅ **Logging rules** — level, format, rotation
- ✅ **Performance rules** — query, cache, asset
- ✅ **Git rules** — branch, commit, PR
- ✅ **Deployment rules** — checklist, steps, rollback
- ✅ **Testing rules** — manual, browser, responsive
- ✅ **Documentation rules** — README, code docs

**Dokumen ini siap dipakai sebagai panduan implementasi.**

---

## 📁 File Terkait

| File | Deskripsi |
|:---|:---|
| `PRD.md` | Product Requirements Document |
| `ARCHITECTURE.md` | Architecture Document |
| `RULES.md` | Dokumen ini |
| `SCHEMA.md` | Database schema |

---


