
# 🏗️ Architecture Document — PixiCDN

**Versi:** 1.0
**Tanggal:** 10 Oktober 2026
**Status:** Final untuk Implementasi
**Author:** Raka Restu Saputra

---

## 📋 Daftar Isi

1. [Overview](#1-overview)
2. [System Architecture](#2-system-architecture)
3. [Infrastructure Layer](#3-infrastructure-layer)
4. [Application Layer](#4-application-layer)
5. [Data Layer](#5-data-layer)
6. [Network & Security](#6-network--security)
7. [Data Flow](#7-data-flow)
8. [Directory Structure](#8-directory-structure)
9. [Technology Stack](#9-technology-stack)
10. [Deployment Architecture](#10-deployment-architecture)
11. [Scalability & Performance](#11-scalability--performance)
12. [Monitoring & Observability](#12-monitoring--observability)
13. [Disaster Recovery](#13-disaster-recovery)
14. [Future Architecture](#14-future-architecture)

---

## 1. Overview

### 1.1 Tujuan Dokumen

Dokumen ini mendefinisikan **arsitektur teknis** PixiCDN, mencakup:
- Struktur infrastruktur (server, network, storage)
- Struktur aplikasi (frontend, backend, API)
- Alur data (upload, akses, autentikasi)
- Keamanan berlapis
- Skalabilitas dan pemeliharaan

### 1.2 Prinsip Arsitektur

| Prinsip | Penerapan |
|:---|:---|
| **Separation of Concerns** | Frontend (Server 2) terpisah dari data (Server 3) |
| **Stateless Application** | Session disimpan di server, bukan di aplikasi |
| **Object Storage** | File gambar disimpan di RustFS, bukan di database |
| **Defense in Depth** | Multiple security layers |
| **Fail-Safe Defaults** | Semua akses denied by default |
| **Simplicity First** | Native PHP, no framework |

### 1.3 Konteks Sistem

PixiCDN dibangun di atas **infrastruktur cloud yang sudah ada** (IaaS + PaaS), dengan penambahan layer **SaaS (Software as a Service)**.

```
┌─────────────────────────────────────────────────────────────┐
│                      SaaS LAYER                              │
│                  (PixiCDN Application)                       │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Frontend (HTML/CSS/JS)                                │ │
│  │  Backend (PHP Native)                                  │ │
│  │  API (REST)                                            │ │
│  └────────────────────────────────────────────────────────┘ │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                      PaaS LAYER                              │
│  (Podman, Caddy, PHP-FPM, MariaDB, RustFS, Cloudflare)      │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                      IaaS LAYER                              │
│  (Proxmox VE, LXC Container, VirtualBox, Networking)        │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. System Architecture

### 2.1 High-Level Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                         INTERNET                                  │
│                                                                   │
│                       User / Browser                              │
└─────────────────────────────┬────────────────────────────────────┘
                              │
                              │ HTTPS (443)
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                       CLOUDFLARE EDGE                             │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │ │
│  │  │   DNS        │  │   SSL/TLS    │  │   CDN Cache  │      │ │
│  │  │              │  │  (Flexible)  │  │   (Static)   │      │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘      │ │
│  │                                                              │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │ │
│  │  │  Firewall    │  │  Rate Limit  │  │   DDoS       │      │ │
│  │  │  (WAF)       │  │              │  │  Protection  │      │ │
│  │  └──────────────┘  └──────────────┘  └──────────────┘      │ │
│  └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────┬────────────────────────────────────┘
                              │
                              │ Cloudflare Tunnel
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                    PROXMOX HOST (192.168.1.35)                    │
│                                                                   │
│  ┌───────────────────────────┐  ┌───────────────────────────┐   │
│  │  LXC 101 (Server 2)       │  │  LXC 102 (Server 3)       │   │
│  │  192.168.1.48             │  │  192.168.1.50             │   │
│  │                           │  │                           │   │
│  │  ┌─────────────────────┐  │  │  ┌─────────────────────┐  │   │
│  │  │  Caddy              │  │  │  │  MariaDB            │  │   │
│  │  │  (Web Server)       │  │  │  │  (Database)         │  │   │
│  │  │  :80, :443          │  │  │  │  :3306              │  │   │
│  │  └─────────────────────┘  │  │  └─────────────────────┘  │   │
│  │                           │  │                           │   │
│  │  ┌─────────────────────┐  │  │  ┌─────────────────────┐  │   │
│  │  │  PHP-FPM            │  │  │  │  RustFS             │  │   │
│  │  │  (PHP Runtime)      │  │  │  │  (Object Storage)   │  │   │
│  │  │  :9000              │  │  │  │  :9000, :9001       │  │   │
│  │  └─────────────────────┘  │  │  └─────────────────────┘  │   │
│  │                           │  │                           │   │
│  │  ┌─────────────────────┐  │  │  ┌─────────────────────┐  │   │
│  │  │  File Browser       │  │  │  │  phpMyAdmin         │  │   │
│  │  │  (File UI)          │  │  │  │  (DB GUI)           │  │   │
│  │  │  :8080              │  │  │  │  :8081              │  │   │
│  │  └─────────────────────┘  │  │  └─────────────────────┘  │   │
│  │                           │  │                           │   │
│  │  ┌─────────────────────┐  │  │  ┌─────────────────────┐  │   │
│  │  │  Beszel Agent       │  │  │  │  Beszel Agent       │  │   │
│  │  │  (Monitoring)       │  │  │  │  (Monitoring)       │  │   │
│  │  │  :45876             │  │  │  │  :45876             │  │   │
│  │  └─────────────────────┘  │  │  └─────────────────────┘  │   │
│  └───────────────────────────┘  └───────────────────────────┘   │
│                                                                   │
│  Network: vmbr0 (192.168.1.0/24)                                 │
└──────────────────────────────────────────────────────────────────┘
```

### 2.2 Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                       PIXICDN APPLICATION                        │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Frontend   │  │   Backend    │  │     API      │          │
│  │              │  │              │  │              │          │
│  │  HTML/CSS/JS │  │  PHP Native  │  │  REST JSON   │          │
│  │  Tailwind    │  │  PDO         │  │  API Key     │          │
│  │  Toastify    │  │  AWS SDK     │  │  Rate Limit  │          │
│  │  FileBokz    │  │  PHPMailer   │  │  Logging     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                 │                 │                   │
│         └─────────────────┼─────────────────┘                   │
│                           │                                     │
│                           ▼                                     │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              Shared Components                          │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐            │    │
│  │  │ config   │  │ helpers  │  │ auth     │            │    │
│  │  │ .php     │  │ .php     │  │ .php     │            │    │
│  │  └──────────┘  └──────────┘  └──────────┘            │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
              ▼                ▼                ▼
       ┌────────────┐   ┌────────────┐   ┌────────────┐
       │  MariaDB   │   │  RustFS    │   │   SMTP     │
       │  (Metadata)│   │  (Files)   │   │  (Email)   │
       └────────────┘   └────────────┘   └────────────┘
```

---

## 3. Infrastructure Layer

### 3.1 Server Inventory

| Server | Hostname | IP | CPU | RAM | Disk | Peran |
|:---|:---|:---|:---|:---|:---|:---|
| **Proxmox** | `proxmoxcompute` | 192.168.1.35 | - | - | - | Hypervisor |
| **Server 1** | `debian-alpha` | 192.168.1.47 | 2 | 4 GB | 16 GB | Gateway & Monitoring |
| **Server 2** | `debian` | 192.168.1.48 | 2 | 2 GB | 12 GB | Web & Application |
| **Server 3** | `debian-db` | 192.168.1.50 | 2 | 2 GB | 10 GB | Database & Storage |

### 3.2 Server 2 — Application Server (Detail)

**Hostname:** `debian`
**IP:** `192.168.1.48`
**OS:** Debian 12 (Bookworm)
**Type:** LXC Container (Unprivileged)

**Services:**

| Service | Type | Port | Fungsi |
|:---|:---|:---|:---|
| **Caddy** | Container (Podman) | 80, 443 | Web server, reverse proxy |
| **PHP-FPM** | Container (Podman) | 9000 | PHP runtime |
| **File Browser** | Container (Podman) | 8080 | File management UI |
| **Beszel Agent** | Container (Podman) | 45876 | System monitoring |

**Direktori Utama:**

```
/opt/
├── caddy/
│   ├── Caddyfile           # Konfigurasi Caddy
│   ├── html/               # Document root
│   │   ├── _shared/       # Library shared
│   │   ├── pixicdn/       # Project PixiCDN
│   │   └── ...
│   ├── data/               # Caddy data
│   └── config/             # Caddy config
├── php/
│   ├── Dockerfile          # Custom PHP-FPM image
│   └── ...
├── filebrowser/
│   └── data/               # File Browser data
└── beszel/
    └── agent.env           # Beszel config
```

### 3.3 Server 3 — Data Server (Detail)

**Hostname:** `debian-db`
**IP:** `192.168.1.50`
**OS:** Debian 12 (Bookworm)
**Type:** LXC Container (Unprivileged)

**Services:**

| Service | Type | Port | Fungsi |
|:---|:---|:---|:---|
| **MariaDB** | Container (Podman) | 3306 | Database server |
| **phpMyAdmin** | Container (Podman) | 8081 | Database GUI |
| **RustFS** | Native (binary) | 9000, 9001 | Object storage |
| **Beszel Agent** | Container (Podman) | 45876 | System monitoring |

**Direktori Utama:**

```
/opt/
├── mariadb/
│   ├── data/               # Data MariaDB
│   └── conf/               # Konfigurasi
├── rustfs/
│   └── data/               # Data RustFS (atau /data)
└── beszel/
    └── agent.env           # Beszel config
```

### 3.4 Container Orchestration

**Metode:** Podman + Quadlet (systemd native)

```
┌─────────────────────────────────────────────────────────────┐
│  systemd (PID 1)                                            │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Quadlet Generator                                     │ │
│  │  Membaca /etc/containers/systemd/*.container          │ │
│  └────────────────────┬───────────────────────────────────┘ │
│                       │                                      │
│                       ▼                                      │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  systemd services                                      │ │
│  │  - caddy.service                                       │ │
│  │  - phpfpm.service                                      │ │
│  │  - filebrowser.service                                 │ │
│  │  - mariadb.service                                     │ │
│  │  - phpmyadmin.service                                  │ │
│  │  - beszel-agent.service                                │ │
│  └────────────────────┬───────────────────────────────────┘ │
│                       │                                      │
│                       ▼                                      │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Podman containers                                     │ │
│  │  (running as services)                                 │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

**Autostart:** Ya, via `[Install] WantedBy=default.target`.

---

## 4. Application Layer

### 4.1 Application Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     PIXICDN APPLICATION                          │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  PRESENTATION LAYER                                       │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐ │   │
│  │  │ Landing  │  │ Gallery  │  │Dashboard │  │  Auth    │ │   │
│  │  │  Page    │  │  Page    │  │  Page    │  │  Pages   │ │   │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘ │   │
│  │                                                           │   │
│  │  HTML + Tailwind CSS + Vanilla JS                        │   │
│  │  Libraries: FileBokz, Toastify, Lucide, Chart.js         │   │
│  └──────────────────────┬───────────────────────────────────┘   │
│                         │                                        │
│  ┌──────────────────────▼───────────────────────────────────┐   │
│  │  APPLICATION LAYER (Business Logic)                       │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐ │   │
│  │  │  Upload  │  │  Image   │  │   Auth   │  │   API    │ │   │
│  │  │ Handler  │  │ Manager  │  │ Manager  │  │ Handler  │ │   │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘ │   │
│  │                                                           │   │
│  │  PHP Native (tanpa framework)                            │   │
│  │  - Validasi input                                        │   │
│  │  - Business rules                                        │   │
│  │  - Authorization                                         │   │
│  └──────────────────────┬───────────────────────────────────┘   │
│                         │                                        │
│  ┌──────────────────────▼───────────────────────────────────┐   │
│  │  DATA ACCESS LAYER                                        │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐              │   │
│  │  │   PDO    │  │  AWS SDK │  │  Session │              │   │
│  │  │ (MariaDB)│  │ (RustFS) │  │ (Native) │              │   │
│  │  └──────────┘  └──────────┘  └──────────┘              │   │
│  └──────────────────────┬───────────────────────────────────┘   │
│                         │                                        │
└─────────────────────────┼────────────────────────────────────────┘
                          │
              ┌───────────┼───────────┐
              │           │           │
              ▼           ▼           ▼
       ┌──────────┐ ┌──────────┐ ┌──────────┐
       │ MariaDB  │ │  RustFS  │ │ Session  │
       │          │ │          │ │  Files   │
       └──────────┘ └──────────┘ └──────────┘
```

### 4.2 Request Lifecycle

```
1. User request → https://pixicdn.erdtreeproject.my.id/upload.php
                              │
2. Cloudflare Edge            │
   - SSL termination          │
   - Cek cache (skip untuk PHP)
   - Forward ke tunnel        │
                              │
3. Cloudflare Tunnel          │
   - Forward ke Server 2      │
                              │
4. Caddy (Server 2)           │
   - Terima request HTTP      │
   - Cek file .php            │
   - Forward ke PHP-FPM       │
                              │
5. PHP-FPM (Server 2)         │
   - Eksekusi upload.php      │
   - Validasi input           │
   - Koneksi ke MariaDB       │
   - Upload ke RustFS         │
   - Return response          │
                              │
6. Response → User            │
   - HTML + JSON data         │
                              ▼
```

### 4.3 Application Modules

| Module | File | Fungsi |
|:---|:---|:---|
| **Upload** | `upload.php` | Handle upload gambar |
| **Gallery** | `gallery.php` | Tampilkan gallery |
| **Image Viewer** | `image.php` | Direct link viewer |
| **Delete** | `delete.php` | Hapus gambar |
| **Register** | `auth/register.php` | Registrasi user |
| **Login** | `auth/login.php` | Login user |
| **Logout** | `auth/logout.php` | Logout user |
| **Profile** | `auth/profile.php` | Edit profile |
| **Forgot Password** | `auth/forgot-password.php` | Request reset |
| **Reset Password** | `auth/reset-password.php` | Reset password |
| **Dashboard** | `dashboard/index.php` | User dashboard |
| **API Upload** | `api/upload.php` | REST API upload |
| **API List** | `api/list.php` | REST API list images |
| **API Delete** | `api/delete.php` | REST API delete |

### 4.4 Authentication Flow

```
┌─────────────────────────────────────────────────────────────┐
│  REGISTER                                                    │
│                                                              │
│  1. User input username + password                          │
│  2. Validasi (client-side + server-side)                    │
│  3. Generate recovery code (8 char)                         │
│  4. Hash password (bcrypt cost 12)                          │
│  5. Hash recovery code (bcrypt cost 12)                     │
│  6. Insert ke tabel users                                   │
│  7. Tampilkan recovery code (sekali saja)                   │
│  8. User centang "sudah simpan"                             │
│  9. Auto-login → dashboard                                  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  LOGIN                                                       │
│                                                              │
│  1. User input username + password                          │
│  2. Query user by username                                  │
│  3. Verify password (bcrypt)                                │
│  4. Jika valid:                                             │
│     - session_regenerate_id()                               │
│     - Set $_SESSION['user_id']                              │
│     - Update last_login_at                                  │
│     - Redirect ke dashboard                                 │
│  5. Jika tidak:                                             │
│     - Set flash error                                       │
│     - Redirect ke login                                     │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  FORGOT PASSWORD                                             │
│                                                              │
│  1. User input username + recovery code                     │
│  2. Query user by username                                  │
│  3. Verify recovery code (bcrypt)                           │
│  4. Jika valid:                                             │
│     - Set $_SESSION['reset_user_id']                        │
│     - Redirect ke reset-password.php                        │
│  5. Jika tidak:                                             │
│     - Response sama (no enumeration)                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Data Layer

### 5.1 Data Storage Strategy

```
┌─────────────────────────────────────────────────────────────┐
│                    DATA STORAGE LAYERS                       │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  STRUCTURED DATA (MariaDB)                             │ │
│  │  - User data (users)                                   │ │
│  │  - Image metadata (images)                             │ │
│  │  - Password reset tokens (password_resets)             │ │
│  │  - Analytics (view_logs, api_logs)                     │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  UNSTRUCTURED DATA (RustFS)                            │ │
│  │  - Gambar asli (jpg, png, gif, webp)                   │ │
│  │  - Thumbnail                                           │ │
│  │  - File lain (jika ada)                                │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  EPHEMERAL DATA (Session Files)                        │ │
│  │  - Session user                                        │ │
│  │  - Flash messages                                      │ │
│  │  - CSRF tokens                                         │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Database Connection

```
┌─────────────────────────────────────────────────────────────┐
│  PHP-FPM (Server 2)                                          │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  PDO Connection                                        │ │
│  │  mysql:host=192.168.1.50;port=3306;dbname=pixicdn_db  │ │
│  │                                                        │ │
│  │  User: pixicdn_user                                    │ │
│  │  Password: ******                                      │ │
│  └────────────────────────┬───────────────────────────────┘ │
└───────────────────────────┼──────────────────────────────────┘
                            │
                            │ TCP/IP
                            │ Local network (192.168.1.0/24)
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  MariaDB (Server 3)                                          │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Database: pixicdn_db                                  │ │
│  │  - users                                               │ │
│  │  - images                                              │ │
│  │  - password_resets                                     │ │
│  │  - view_logs                                           │ │
│  │  - api_logs                                            │ │
│  │  - albums                                              │ │
│  │  - album_images                                        │ │
│  │  - rate_limits                                         │ │
│  │  - audit_logs                                          │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 5.3 Object Storage Access

```
┌─────────────────────────────────────────────────────────────┐
│  PHP-FPM (Server 2)                                          │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  AWS SDK for PHP                                       │ │
│  │                                                        │ │
│  │  Endpoint: http://192.168.1.50:9000                    │ │
│  │  Bucket: pixicdn                                       │ │
│  │  Access Key: rustfsadmin                               │ │
│  │  Secret Key: ******                                    │ │
│  │  Region: us-east-1                                     │ │
│  │  Use Path Style: true                                  │ │
│  └────────────────────────┬───────────────────────────────┘ │
└───────────────────────────┼──────────────────────────────────┘
                            │
                            │ S3 API (HTTP)
                            │ Local network
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  RustFS (Server 3)                                           │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Bucket: pixicdn                                       │ │
│  │  ├── uploads/                                          │ │
│  │  │   ├── 2026/10/abc123.jpg                            │ │
│  │  │   ├── 2026/10/def456.png                            │ │
│  │  │   └── ...                                           │ │
│  │  └── thumbnails/                                       │ │
│  │      ├── thumb_abc123.jpg                              │ │
│  │      └── ...                                           │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  Data: /data/rustfs0 (atau /opt/rustfs/data)                │
│  Public Access: Enable (untuk akses gambar)                 │
└─────────────────────────────────────────────────────────────┘
```

### 5.4 File Naming Convention

```
Format: {YYYYMMDD}_{random}.{ext}

Contoh:
- 20261010_a3f8c2.jpg
- 20261010_def456.png
- 20261010_ghi789.webp

Thumbnail:
- thumb_20261010_a3f8c2.jpg

S3 Key Structure:
- uploads/{YYYY}/{MM}/{filename}
- thumbnails/{YYYY}/{MM}/thumb_{filename}

Contoh S3 Key:
- uploads/2026/10/20261010_a3f8c2.jpg
- thumbnails/2026/10/thumb_20261010_a3f8c2.jpg
```

---

## 6. Network & Security

### 6.1 Network Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     INTERNET                                 │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ HTTPS (443)
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  CLOUDFLARE EDGE                             │
│                  (Global Anycast Network)                    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ Cloudflare Tunnel
                           │ (outbound-only, encrypted)
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  HOME NETWORK                                │
│                  Router: 192.168.1.1                          │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Proxmox Host: 192.168.1.35                          │  │
│  │                                                       │  │
│  │  ┌─────────────────┐  ┌─────────────────┐           │  │
│  │  │  Server 2       │  │  Server 3       │           │  │
│  │  │  192.168.1.48   │  │  192.168.1.50   │           │  │
│  │  │                 │  │                 │           │  │
│  │  │  Caddy :80,443  │  │  MariaDB :3306  │           │  │
│  │  │  PHP-FPM :9000  │  │  RustFS :9000   │           │  │
│  │  │  FileBrowser    │  │  phpMyAdmin     │           │  │
│  │  │    :8080        │  │    :8081        │           │  │
│  │  └────────┬────────┘  └────────┬────────┘           │  │
│  │           │                    │                     │  │
│  │           └────────┬───────────┘                     │  │
│  │                    │                                 │  │
│  │           Local Network (vmbr0)                      │  │
│  │           192.168.1.0/24                             │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 6.2 Cloudflare Tunnel Configuration

**Tunnel:** `proxmox-home`
**Type:** Outbound-only
**Encryption:** TLS 1.3

**Routes:**

| Subdomain | Target | Service |
|:---|:---|:---|
| `pixicdn.erdtreeproject.my.id` | `192.168.1.48:80` | Frontend + Upload |
| `s3.erdtreeproject.my.id` | `192.168.1.50:9000` | RustFS API |
| `rustfs.erdtreeproject.my.id` | `192.168.1.50:9001` | RustFS Console |
| `db.erdtreeproject.my.id` | `192.168.1.50:8081` | phpMyAdmin |
| `files.erdtreeproject.my.id` | `192.168.1.48:8080` | File Browser |
| `*` | `192.168.1.48:80` | Wildcard |

**Origin Configuration (untuk `pixicdn.*`):**

| Setting | Nilai |
|:---|:---|
| **HTTP Host Header** | `pixicdn.erdtreeproject.my.id` |
| **No TLS Verify** | ✅ |
| **Disable Chunked Encoding** | ✅ |

### 6.3 Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│  LAYER 1: TRANSPORT SECURITY                                 │
│  - HTTPS via Cloudflare SSL                                  │
│  - TLS 1.3                                                   │
│  - HSTS enabled                                              │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  LAYER 2: NETWORK SECURITY                                   │
│  - Cloudflare Tunnel (outbound-only)                        │
│  - No port forwarding                                        │
│  - DDoS protection                                           │
│  - WAF (Web Application Firewall)                            │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  LAYER 3: AUTHENTICATION                                     │
│  - Password: bcrypt (cost 12)                                │
│  - Session: httponly, secure, samesite                       │
│  - CSRF tokens                                               │
│  - Rate limiting                                             │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  LAYER 4: AUTHORIZATION                                      │
│  - Role-based access (user, admin)                          │
│  - Ownership check (user hanya bisa akses gambar sendiri)    │
│  - API key validation                                        │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  LAYER 5: APPLICATION SECURITY                               │
│  - Input validation                                          │
│  - Output escaping (htmlspecialchars)                        │
│  - Prepared statements (PDO)                                 │
│  - File type validation                                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  LAYER 6: DATA SECURITY                                      │
│  - Encrypted at rest (MariaDB)                               │
│  - Access control (user, password)                           │
│  - Backup encryption                                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  LAYER 7: AUDIT & LOGGING                                    │
│  - Audit logs (login, upload, delete)                        │
│  - API logs                                                  │
│  - View logs                                                 │
│  - Error logs                                                │
└─────────────────────────────────────────────────────────────┘
```

### 6.4 Security Headers (Caddy)

```caddy
header {
    # Prevent MIME sniffing
    X-Content-Type-Options "nosniff"
    
    # Prevent clickjacking
    X-Frame-Options "SAMEORIGIN"
    
    # XSS protection
    X-XSS-Protection "1; mode=block"
    
    # Referrer policy
    Referrer-Policy "strict-origin-when-cross-origin"
    
    # Content Security Policy
    Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'"
    
    # Permissions policy
    Permissions-Policy "camera=(), microphone=(), geolocation=()"
}
```

---

## 7. Data Flow

### 7.1 Upload Flow (Detailed)

```
┌─────────────────────────────────────────────────────────────┐
│  STEP 1: USER INTERACTION                                    │
│                                                              │
│  User drag & drop gambar ke FileBokz                        │
│  FileBokz validasi:                                          │
│  - Ekstensi: jpg, jpeg, png, gif, webp                      │
│  - Max size: 5 MB                                            │
│  - Max files: 5                                              │
│  - Preview gambar                                            │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 2: FORM SUBMIT                                         │
│                                                              │
│  POST /upload.php                                            │
│  Content-Type: multipart/form-data                           │
│  Body: images[] = file1, file2, ...                          │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 3: SERVER VALIDATION (PHP)                             │
│                                                              │
│  - Cek CSRF token                                            │
│  - Cek rate limit (max 100 upload/jam)                       │
│  - Cek MIME type (getimagesize)                              │
│  - Cek ukuran (max 5 MB)                                     │
│  - Cek ekstensi (whitelist)                                  │
│  - Cek quota storage user                                    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 4: FILE PROCESSING                                     │
│                                                              │
│  - Generate filename: YYYYMMDD_{random}.{ext}               │
│  - Generate S3 key: uploads/{YYYY}/{MM}/{filename}          │
│  - Generate thumbnail (opsional)                             │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 5: UPLOAD TO RUSTFS                                    │
│                                                              │
│  AWS SDK:                                                    │
│  $s3->putObject([                                            │
│      'Bucket' => 'pixicdn',                                  │
│      'Key' => $s3Key,                                        │
│      'SourceFile' => $tmpPath,                               │
│      'ContentType' => $mimeType,                             │
│  ]);                                                         │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 6: SAVE METADATA TO MARIADB                            │
│                                                              │
│  INSERT INTO images (                                        │
│      user_id, filename, original_name, s3_key,              │
│      public_url, mime_type, extension, size,                │
│      width, height, ip_address                              │
│  ) VALUES (...)                                              │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 7: UPDATE USER STORAGE                                 │
│                                                              │
│  UPDATE users                                                │
│  SET storage_used = storage_used + ?                        │
│  WHERE id = ?                                                │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 8: RETURN RESPONSE                                     │
│                                                              │
│  JSON Response:                                              │
│  {                                                           │
│    "success": true,                                          │
│    "data": {                                                 │
│      "id": 123,                                              │
│      "url": "https://pixicdn.../abc123.jpg",                │
│      "thumbnail": "https://pixicdn.../thumb_abc123.jpg",    │
│      "size": 245760,                                         │
│      "views": 0                                              │
│    }                                                         │
│  }                                                           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  STEP 9: TOAST NOTIFICATION                                  │
│                                                              │
│  Toastify.showToast('Upload Berhasil', 'Gambar telah...')   │
│  Tampilkan direct link + BBCode + HTML                       │
└─────────────────────────────────────────────────────────────┘
```

### 7.2 Access Flow (Image Viewer)

```
User akses https://pixicdn.erdtreeproject.my.id/abc123.jpg
        │
        ▼
Cloudflare Edge (cache check)
        │
        ├─── Cache HIT ──► Return cached image
        │
        └─── Cache MISS
                │
                ▼
        Cloudflare Tunnel
                │
                ▼
        RustFS (Server 3) :9000
                │
                ├─── File exists ──► Return image + cache
                │
                └─── File not found ──► 404
```

### 7.3 Authentication Flow (Login)

```
┌─────────────────────────────────────────────────────────────┐
│  1. User buka /auth/login.php                               │
│  2. Tampilkan form login (username + password)              │
│  3. User submit form                                        │
│  4. POST /auth/login.php                                    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  5. Server (PHP):                                            │
│     a. Cek CSRF token                                        │
│     b. Cek rate limit (max 5 login/menit)                   │
│     c. Query user by username                                │
│     d. Verify password (bcrypt)                              │
│     e. Jika valid:                                           │
│        - session_regenerate_id()                             │
│        - $_SESSION['user_id'] = $user['id']                  │
│        - Update last_login_at                                │
│        - Insert audit_log (login success)                    │
│        - Redirect ke dashboard                               │
│     f. Jika tidak valid:                                     │
│        - Insert audit_log (login failed)                     │
│        - Set flash error                                     │
│        - Redirect ke login                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. Directory Structure

### 8.1 Project Structure

```
/opt/caddy/html/pixicdn/
├── config.php                    # Konfigurasi utama
├── helpers.php                   # Fungsi helper
├── .htaccess                     # (jika pakai Apache)
├── composer.json                 # Composer dependencies
├── composer.lock
│
├── index.php                     # Landing page + upload
├── upload.php                    # Handler upload
├── gallery.php                   # Gallery gambar
├── image.php                     # Direct link viewer
├── delete.php                    # Handler delete
├── 404.php                       # Custom 404 page
│
├── auth/                         # Authentication
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   ├── forgot-password.php
│   ├── reset-password.php
│   ├── profile.php
│   └── api-key.php
│
├── dashboard/                    # User dashboard
│   ├── index.php
│   ├── my-images.php
│   └── settings.php
│
├── api/                          # REST API
│   ├── upload.php
│   ├── list.php
│   ├── delete.php
│   └── me.php
│
├── admin/                        # Admin panel (opsional)
│   ├── index.php
│   ├── users.php
│   └── images.php
│
├── cron/                         # Cron jobs
│   ├── cleanup-expired.php
│   └── update-storage.php
│
├── assets/                       # Static assets
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   ├── main.js
│   │   ├── upload.js
│   │   └── toast.js
│   └── img/
│       └── logo.svg
│
├── includes/                     # Shared includes
│   ├── header.php
│   ├── footer.php
│   ├── nav.php
│   └── auth-check.php
│
├── migrations/                   # Database migrations
│   ├── 001_initial_schema.sql
│   └── 002_sample_data.sql
│
├── logs/                         # Application logs
│   ├── error.log
│   └── access.log
│
├── uploads/                      # Temp upload (jika perlu)
│
└── vendor/                       # Composer packages
    ├── aws/aws-sdk-php/
    ├── phpmailer/phpmailer/
    └── autoload.php
```

### 8.2 Configuration Files

**`config.php`:**

```php
<?php
// ============================================================
// DATABASE (Server 3)
// ============================================================
define('DB_HOST', '192.168.1.50');
define('DB_PORT', '3306');
define('DB_NAME', 'pixicdn_db');
define('DB_USER', 'pixicdn_user');
define('DB_PASS', 'PixiCDN@2026!');

// ============================================================
// RUSTFS (Server 3)
// ============================================================
define('S3_ENDPOINT', 'http://192.168.1.50:9000');
define('S3_ACCESS_KEY', 'adminrust123');
define('S3_SECRET_KEY', 'adminrust123');
define('S3_BUCKET', 'pixicdn');
define('S3_REGION', 'us-east-1');
define('S3_PUBLIC_URL', 'https://pixicdn.erdtreeproject.my.id');

// ============================================================
// APLIKASI
// ============================================================
define('APP_NAME', 'PixiCDN');
define('APP_URL', 'https://pixicdn.erdtreeproject.my.id');
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Asia/Jakarta');

// ============================================================
// UPLOAD
// ============================================================
define('MAX_FILE_SIZE', 5 * 1024 * 1024);  // 5 MB
define('MAX_FILES_PER_UPLOAD', 5);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIMES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp'
]);

// ============================================================
// STORAGE
// ============================================================
define('DEFAULT_STORAGE_QUOTA', 100 * 1024 * 1024);  // 100 MB

// ============================================================
// SESSION
// ============================================================
define('SESSION_LIFETIME', 3600);  // 1 jam
define('SESSION_NAME', 'PIXICDN_SESSION');

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set(APP_TIMEZONE);

// ============================================================
// ERROR REPORTING
// ============================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// ============================================================
// SESSION CONFIG
// ============================================================
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// ============================================================
// DATABASE CONNECTION
// ============================================================
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log("DB Connection Failed: " . $e->getMessage());
            die("Terjadi kesalahan pada sistem.");
        }
    }
    return $pdo;
}

// ============================================================
// S3 CLIENT (RustFS)
// ============================================================
function getS3() {
    static $s3 = null;
    if ($s3 === null) {
        require_once __DIR__ . '/vendor/autoload.php';
        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region' => S3_REGION,
            'endpoint' => S3_ENDPOINT,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => S3_ACCESS_KEY,
                'secret' => S3_SECRET_KEY,
            ],
        ]);
    }
    return $s3;
}
```

---

## 9. Technology Stack

### 9.1 Full Stack Overview

| Layer | Teknologi | Version | Fungsi |
|:---|:---|:---|:---|
| **Hypervisor** | Proxmox VE | 8.x | Virtualization |
| **Container** | LXC | - | OS-level virtualization |
| **Container Engine** | Podman | 5.x | Container runtime |
| **Autostart** | Quadlet | - | Systemd generator |
| **Web Server** | Caddy | 2.x | HTTP server |
| **PHP Runtime** | PHP-FPM | 8.2 | PHP FastCGI |
| **Database** | MariaDB | 11 | RDBMS |
| **Object Storage** | RustFS | Latest | S3-compatible |
| **CDN** | Cloudflare | - | Edge network |
| **Tunnel** | cloudflared | Latest | Reverse tunnel |
| **Monitoring** | Beszel | Latest | System monitoring |
| **Monitoring** | Uptime Kuma | 1.x | Uptime check |
| **Reverse Proxy** | Traefik | 3.x | (Server 1) |

### 9.2 Backend Stack

| Komponen | Teknologi | Alasan |
|:---|:---|:---|
| **Bahasa** | PHP 8.2 | Familiar, ringan |
| **Framework** | Native (tanpa framework) | Simpel, edukatif |
| **Database Access** | PDO | Prepared statements |
| **S3 Client** | AWS SDK for PHP | Standard, teruji |
| **Email** | PHPMailer | Fleksibel, dokumentasi baik |
| **Session** | Native PHP | Built-in |
| **Password Hashing** | bcrypt | Standard industri |
| **Autoloading** | Composer | Standard |

### 9.3 Frontend Stack

| Komponen | Teknologi | CDN |
|:---|:---|:---|
| **Styling** | Tailwind CSS v4 | jsDelivr |
| **Icons** | Lucide | unpkg |
| **Toast** | Toastify (zzev) | jsDelivr |
| **Upload** | FileBokz | jsDelivr |
| **Chart** | Chart.js | jsDelivr |
| **Modal** | SweetAlert2 | jsDelivr |
| **Scripting** | Vanilla JS | - |
| **Markup** | HTML5 | - |

### 9.4 Library Dependencies

**Backend (Composer):**

```json
{
    "require": {
        "aws/aws-sdk-php": "^3.300",
        "phpmailer/phpmailer": "^6.9"
    }
}
```

**Frontend (CDN):**

```html
<!-- Tailwind CSS v4 -->
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- Toastify (zzev) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@zzev/toastify/dist/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/@zzev/toastify/dist/toastify.min.js"></script>

<!-- FileBokz -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fivefifteen/filebokz@0.2/dist/filebokz.min.css">
<script src="https://cdn.jsdelivr.net/gh/fivefifteen/filebokz@0.2/dist/filebokz.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

---

## 10. Deployment Architecture

### 10.1 Deployment Flow

```
┌─────────────────────────────────────────────────────────────┐
│  DEVELOPMENT (Laptop)                                        │
│                                                              │
│  ─ Code editor (VS Code + Remote SSH)                       │
│  ─ Git repository (GitHub)                                   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ git push
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  GITHUB                                                      │
│                                                              │
│  ─ Repository: Raka-coder/pixicdn                           │
│  ─ Branch: main                                              │
│  ─ Webhook: /opt/scripts/deploy.sh                          │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ webhook (HTTP POST)
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  SERVER 2 (debian, 192.168.1.48)                            │
│                                                              │
│  ─ Webhook listener: /_shared/webhook.php                   │
│  ─ Deploy script: /opt/scripts/deploy.sh                    │
│  ─ Git pull → composer install → restart services           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           │ git pull
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  /opt/caddy/html/pixicdn/                                   │
│                                                              │
│  ─ Updated code                                              │
│  ─ Reinstall dependencies (jika perlu)                      │
└─────────────────────────────────────────────────────────────┘
```

### 10.2 Deployment Steps (Manual)

```bash
# 1. SSH ke Server 2
ssh ssh-debian

# 2. Masuk ke folder project
cd /opt/caddy/html/pixicdn

# 3. Pull latest code
sudo git pull origin main

# 4. Install/update dependencies
sudo composer install --no-dev --optimize-autoloader

# 5. Set permission
sudo chown -R 1000:1000 .
sudo chmod -R 755 .
sudo chmod 640 config.php

# 6. Restart PHP-FPM (jika ada perubahan config)
sudo systemctl restart phpfpm.service

# 7. Test akses
curl -I https://pixicdn.erdtreeproject.my.id
```

### 10.3 Environment Configuration

| Environment | Server 2 | Server 3 |
|:---|:---|:---|
| **Development** | `debian` (LXC 101) | `debian-db` (LXC 102) |
| **Production** | `debian` (LXC 101) | `debian-db` (LXC 102) |
| **URL** | `https://pixicdn.erdtreeproject.my.id` | Internal |
| **Debug** | `APP_DEBUG = true` | - |
| **Error Reporting** | `E_ALL` | - |

---

## 11. Scalability & Performance

### 11.1 Scalability Strategy

```
┌─────────────────────────────────────────────────────────────┐
│  CURRENT (Single Server)                                     │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Server 2 (Web + App)                                  │ │
│  │  ┌──────────┐  ┌──────────┐                           │ │
│  │  │  Caddy   │  │ PHP-FPM  │                           │ │
│  │  └──────────┘  └──────────┘                           │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Server 3 (DB + Storage)                               │ │
│  │  ┌──────────┐  ┌──────────┐                           │ │
│  │  │ MariaDB  │  │  RustFS  │                           │ │
│  │  └──────────┘  └──────────┘                           │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

                          ↓ (jika scaling)

┌─────────────────────────────────────────────────────────────┐
│  SCALED (Multiple Servers)                                   │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Load Balancer (Cloudflare)                            │ │
│  └────────────────────┬───────────────────────────────────┘ │
│                       │                                      │
│         ┌─────────────┼─────────────┐                       │
│         │             │             │                       │
│         ▼             ▼             ▼                       │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                 │
│  │ Server 2A│  │ Server 2B│  │ Server 2C│  (Web + App)    │
│  └──────────┘  └──────────┘  └──────────┘                 │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Server 3 (DB + Storage)                               │ │
│  │  (Shared by all web servers)                           │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### 11.2 Performance Optimization

| Aspek | Optimasi | Dampak |
|:---|:---|:---|
| **Image Delivery** | Cloudflare CDN caching | -80% load time |
| **Thumbnail** | Generate 300x300 untuk gallery | -90% bandwidth |
| **WebP** | Convert JPEG/PNG ke WebP | -30% file size |
| **Lazy Loading** | `loading="lazy"` untuk gambar | Faster initial load |
| **Database** | Indexes pada kolom pencarian | -70% query time |
| **PHP** | OPcache enabled | -50% PHP execution |
| **Connection Pooling** | PDO persistent connection | -30% connection overhead |
| **HTTP/2** | Enabled di Caddy | Multiplexing |
| **Gzip/Brotli** | Enabled di Caddy | -70% text size |

### 11.3 Caching Strategy

| Layer | Cache | TTL | Konten |
|:---|:---|:---|:---|
| **Cloudflare** | Edge cache | 1 tahun | Gambar, static assets |
| **Caddy** | - | - | - |
| **PHP** | OPcache | 1 jam | Compiled PHP |
| **MariaDB** | Query cache | - | (Built-in) |
| **Browser** | HTTP cache | 1 tahun | Gambar, CSS, JS |

**Cache Rules di Cloudflare:**

```
# Gambar: cache 1 tahun
pixicdn.erdtreeproject.my.id/*
  → Cache Level: Cache Everything
  → Edge TTL: 1 year

# PHP: bypass cache
pixicdn.erdtreeproject.my.id/*.php
  → Cache Level: Bypass

# Upload: bypass cache
pixicdn.erdtreeproject.my.id/upload.php
  → Cache Level: Bypass
```

---

## 12. Monitoring & Observability

### 12.1 Monitoring Stack

```
┌─────────────────────────────────────────────────────────────┐
│  SERVER 1 (Monitoring Hub)                                   │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Uptime Kuma (:3001)                                   │ │
│  │  - Monitor HTTP endpoints                              │ │
│  │  - Monitor TCP ports                                   │ │
│  │  - Alert via Telegram                                  │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Beszel Hub (:8090)                                    │ │
│  │  - Terima metrik dari agent                            │ │
│  │  - Dashboard visual                                    │ │
│  │  - Alert threshold                                     │ │
│  └────────────────────────────────────────────────────────┘ │
└──────────────────────────┬──────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         │                 │                 │
         ▼                 ▼                 ▼
   ┌──────────┐      ┌──────────┐     ┌──────────┐
   │ Beszel   │      │ Beszel   │     │ Beszel   │
   │ Agent S1 │      │ Agent S2 │     │ Agent S3 │
   └──────────┘      └──────────┘     └──────────┘
```

### 12.2 Uptime Kuma Monitors

| Monitor | Type | Target | Interval |
|:---|:---|:---|:---|
| **PixiCDN Homepage** | HTTP | `https://pixicdn.erdtreeproject.my.id` | 60s |
| **PixiCDN Upload** | HTTP | `https://pixicdn.erdtreeproject.my.id/upload.php` | 60s |
| **S3 API** | HTTP | `https://s3.erdtreeproject.my.id` | 60s |
| **RustFS Console** | HTTP | `https://rustfs.erdtreeproject.my.id` | 60s |
| **phpMyAdmin** | HTTP | `https://db.erdtreeproject.my.id` | 60s |
| **MariaDB** | TCP | `192.168.1.50:3306` | 60s |
| **RustFS S3** | TCP | `192.168.1.50:9000` | 60s |
| **PHP-FPM** | TCP | `192.168.1.48:9000` | 60s |

### 12.3 Application Logging

**Log Files:**

| Log | Lokasi | Isi |
|:---|:---|:---|
| **Error Log** | `/logs/error.log` | PHP errors |
| **Access Log** | `/var/log/caddy/access.log` | HTTP access |
| **Audit Log** | `audit_logs` table | User actions |
| **API Log** | `api_logs` table | API requests |
| **View Log** | `view_logs` table | Image views |

**Log Format (Access Log):**

```
{
    "level": "info",
    "ts": 1791431354.4652505,
    "logger": "http.log.access",
    "msg": "handled request",
    "request": {
        "remote_ip": "192.168.1.1",
        "remote_port": "54321",
        "client_ip": "203.0.113.1",
        "proto": "HTTP/2.0",
        "method": "POST",
        "host": "pixicdn.erdtreeproject.my.id",
        "uri": "/upload.php",
        "headers": {...}
    },
    "bytes_read": 245760,
    "user_id": "",
    "duration": 0.234,
    "size": 1234,
    "status": 200
}
```

---

## 13. Disaster Recovery

### 13.1 Backup Strategy

| Data | Frekuensi | Retensi | Lokasi |
|:---|:---|:---|:---|
| **MariaDB** | Harian (02:00) | 7 hari | `/opt/backup/mariadb/` |
| **RustFS Config** | Mingguan | 4 minggu | `/opt/backup/rustfs/` |
| **Application Code** | Git | - | GitHub |
| **Caddyfile** | Setiap perubahan | 4 minggu | `/opt/caddy/backup/` |
| **Quadlet Files** | Setiap perubahan | 4 minggu | `/etc/containers/systemd/` |

### 13.2 Backup Script

```bash
#!/bin/bash
# /opt/scripts/backup-all.sh

BACKUP_DIR="/opt/backup"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"/{mariadb,rustfs,caddy,quadlet}

# 1. Backup MariaDB
podman exec mariadb mariadb-dump \
    -u root -p"$MARIADB_ROOT_PASSWORD" \
    --all-databases > "$BACKUP_DIR/mariadb/all-db_$DATE.sql"

# 2. Backup RustFS config
tar -czf "$BACKUP_DIR/rustfs/config_$DATE.tar.gz" \
    /etc/default/rustfs \
    /etc/systemd/system/rustfs.service

# 3. Backup Caddy
tar -czf "$BACKUP_DIR/caddy/config_$DATE.tar.gz" \
    /opt/caddy/Caddyfile

# 4. Backup Quadlet
tar -czf "$BACKUP_DIR/quadlet/quadlet_$DATE.tar.gz" \
    /etc/containers/systemd/

# 5. Cleanup (7 hari)
find "$BACKUP_DIR" -type f -mtime +7 -delete

echo "Backup selesai: $DATE"
```

### 13.3 Recovery Procedures

**Recovery Scenario 1: Database Corrupt**

```bash
# 1. Stop MariaDB
sudo systemctl stop mariadb.service

# 2. Restore from backup
podman exec -i mariadb mariadb -u root -p"$MARIADB_ROOT_PASSWORD" \
    < /opt/backup/mariadb/all-db_20261010_020000.sql

# 3. Start MariaDB
sudo systemctl start mariadb.service

# 4. Verify
mariadb -h 192.168.1.50 -u pixicdn_user -p -e "SHOW DATABASES;"
```

**Recovery Scenario 2: RustFS Down**

```bash
# 1. Cek status
systemctl status rustfs

# 2. Jika service mati, restart
sudo systemctl restart rustfs

# 3. Jika data corrupt, restore config
tar -xzf /opt/backup/rustfs/config_20261010.tar.gz -C /

# 4. Restart
sudo systemctl restart rustfs

# 5. Verify
curl -I http://localhost:9000
```

**Recovery Scenario 3: Server Down**

```bash
# 1. Buat LXC baru di Proxmox
pct create 103 local:vztmpl/debian-12-standard_12.7-1_amd64.tar.zst \
    --hostname debian-restore \
    --password "StrongPass123!" \
    --net0 name=eth0,bridge=vmbr0,ip=192.168.1.48/24,gw=192.168.1.1 \
    --storage local-lvm \
    --memory 2048 --cores 2 \
    --rootfs local-lvm:12

# 2. Install Podman + dependencies
bash -c "$(curl -fsSL https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/ct/podman.sh)"

# 3. Restore Quadlet files
tar -xzf /opt/backup/quadlet/quadlet_20261010.tar.gz -C /

# 4. Restore application code
cd /opt/caddy/html
git clone https://github.com/Raka-coder/pixicdn.git

# 5. Restore Caddy config
tar -xzf /opt/backup/caddy/config_20261010.tar.gz -C /

# 6. Start services
sudo systemctl daemon-reload
sudo systemctl start caddy phpfpm filebrowser beszel-agent
```

---

## 14. Future Architecture

### 14.1 Roadmap Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  PHASE 1: Current (MVP)                                      │
│  ─ Server 2: Web + App                                       │
│  ─ Server 3: DB + Storage                                    │
│  ─ Cloudflare: CDN + Tunnel                                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  PHASE 2: Enhanced (SaaS)                                    │
│  ─ User registration + login                                 │
│  ─ Dashboard + analytics                                     │
│  ─ REST API                                                  │
│  ─ Thumbnail generation                                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  PHASE 3: Advanced                                           │
│  ─ Album management                                          │
│  ─ Custom domain per user                                    │
│  ─ Image optimization (WebP, resize)                         │
│  ─ Email notifications                                       │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  PHASE 4: Scale (Future)                                     │
│  ─ Multiple web servers (load balanced)                     │
│  ─ Redis cache                                               │
│  ─ CDN origin shield                                         │
│  ─ Kubernetes orchestration                                  │
│  ─ Object storage replication                                │
└─────────────────────────────────────────────────────────────┘
```

### 14.2 Potential Improvements

| Area | Improvement | Benefit |
|:---|:---|:---|
| **Performance** | Redis cache untuk session | Faster session lookup |
| **Performance** | Image optimization (WebP) | Smaller file size |
| **Reliability** | Multiple web servers | High availability |
| **Reliability** | Database replication | Backup + failover |
| **Security** | Cloudflare Access | Protect admin panel |
| **Security** | 2FA login | Enhanced security |
| **Features** | Custom domain per user | SaaS premium feature |
| **Features** | API rate limiting | Prevent abuse |
| **Monitoring** | Prometheus + Grafana | Advanced metrics |
| **Monitoring** | Sentry | Error tracking |

### 14.3 Migration Path

**Dari Monolith ke Microservices (jika diperlukan):**

```
┌─────────────────────────────────────────────────────────────┐
│  CURRENT: Monolith                                           │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  PixiCDN (Single Application)                          │ │
│  │  - Upload, Gallery, Auth, API, Dashboard               │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘

                          ↓ (jika scaling)

┌─────────────────────────────────────────────────────────────┐
│  FUTURE: Microservices                                       │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐    │
│  │ Upload   │  │  Auth    │  │  Image   │  │  API     │    │
│  │ Service  │  │ Service  │  │ Service  │  │ Service  │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Message Queue (RabbitMQ / Redis)                      │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Shared: MariaDB, RustFS, Cache                        │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Ringkasan Arsitektur

| Layer | Komponen | Teknologi |
|:---|:---|:---|
| **Client** | Browser | HTML/CSS/JS |
| **CDN** | Cloudflare Edge | SSL, Cache, WAF |
| **Network** | Cloudflare Tunnel | Outbound-only |
| **Web Server** | Caddy | HTTP/2, HTTP/3 |
| **Application** | PHP-FPM | PHP 8.2 |
| **Database** | MariaDB | 11 |
| **Storage** | RustFS | S3-compatible |
| **Monitoring** | Beszel + Uptime Kuma | Metrics + Uptime |
| **Infrastructure** | Proxmox + LXC | Virtualization |

---

## 🎯 Prinsip Arsitektur Final

| # | Prinsip | Implementasi |
|:---|:---|:---|
| 1 | **Separation of Concerns** | Frontend, backend, data terpisah |
| 2 | **Stateless** | Session di server files |
| 3 | **Object Storage** | File di RustFS, bukan DB |
| 4 | **Defense in Depth** | 7 layer security |
| 5 | **Fail-Safe** | Default deny |
| 6 | **Simplicity** | Native PHP, no framework |
| 7 | **Observability** | Monitoring + logging |
| 8 | **Recoverability** | Backup + recovery procedure |

---

## 📁 File Terkait

| File | Deskripsi |
|:---|:---|
| `PRD.md` | Product Requirements Document |
| `ARCHITECTURE.md` | Dokumen ini |
| `RULES.md` | Coding standards + security rules |
| `SCHEMA.md` | Database schema detail |
| `config.php` | Konfigurasi aplikasi |
| `migrations/` | Database migrations |

---

## ✅ Kesimpulan

Dokumen Architecture PixiCDN mencakup:

- ✅ **14 section** lengkap
- ✅ **System architecture** — diagram high-level
- ✅ **Infrastructure layer** — server, network, storage
- ✅ **Application layer** — modul, request lifecycle
- ✅ **Data layer** — database, object storage, session
- ✅ **Network & security** — 7 layer defense
- ✅ **Data flow** — upload, access, authentication
- ✅ **Directory structure** — lengkap
- ✅ **Technology stack** — backend, frontend, library
- ✅ **Deployment** — flow, steps, environment
- ✅ **Scalability** — strategy, optimization
- ✅ **Monitoring** — stack, monitors, logging
- ✅ **Disaster recovery** — backup, recovery
- ✅ **Future architecture** — roadmap

**Dokumen ini siap dipakai sebagai blueprint implementasi.**


