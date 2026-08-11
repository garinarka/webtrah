# Webtrah

**Sistem manajemen silsilah keluarga berbasis web** — lacak anggota keluarga, visualisasikan pohon keluarga, dan kelola perubahan data melalui alur persetujuan multi-level.

![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat&logo=laravel&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=flat&logo=vuedotjs&logoColor=white)
![Inertia](https://img.shields.io/badge/Inertia.js-2-9553E9?style=flat)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat&logo=postgresql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-blue?style=flat)
![CI](https://github.com/garinarka/webtrah/actions/workflows/ci.yml/badge.svg)

---

## Daftar Isi

- [Tentang Proyek](#tentang-proyek)
- [Fitur Utama](#fitur-utama)
- [Stack Teknologi](#stack-teknologi)
- [Prasyarat](#prasyarat)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Struktur Peran](#struktur-peran)
- [Alur Kerja Persetujuan](#alur-kerja-persetujuan)
- [Struktur Direktori](#struktur-direktori)
- [Rute API](#rute-api)
- [Pengembangan](#pengembangan)
- [Lisensi](#lisensi)

---

## Tentang Proyek

Webtrah adalah aplikasi web untuk mendokumentasikan dan mengelola silsilah keluarga secara kolaboratif. Berbeda dari aplikasi pohon keluarga pada umumnya, Webtrah dirancang untuk digunakan oleh komunitas atau organisasi keluarga besar dengan sistem kontrol akses berlapis — setiap perubahan data melewati proses persetujuan sebelum diterapkan, sehingga integritas data tetap terjaga.

### Masalah yang Diselesaikan

Ketika banyak pihak perlu berkontribusi pada data silsilah yang sama, muncul risiko data yang salah, duplikat, atau tidak konsisten. Webtrah mengatasi ini dengan:

- **Alur persetujuan** — setiap penambahan atau perubahan data membutuhkan validasi dari admin atau moderator
- **Audit trail** — seluruh riwayat perubahan tercatat otomatis
- **Scoping per unit keluarga** — moderator hanya mengelola unit keluarga yang ditugaskan kepadanya
- **Mode draft** — data yang belum siap dipublikasikan bisa disimpan sebagai draft dan dilanjutkan kapan saja

---

## Fitur Utama

### Manajemen Data Anggota

- Form wizard 4 langkah: Identitas → Tanggal → Keluarga → Tinjau
- Akurasi tanggal fleksibel: tanggal lengkap, bulan & tahun, atau tahun saja
- Deteksi duplikat otomatis saat input nama
- Auto-save draft saat pengetikan (localStorage)
- Mode draft terpisah dengan halaman `/people/drafts`

### Pohon Keluarga Interaktif

- Visualisasi pohon menggunakan D3.js
- Navigasi drag & zoom
- Pencarian anggota langsung dari tampilan pohon
- Tampilan responsif untuk mobile

### Relasi Keluarga

- Jenis relasi: orang tua kandung, tiri, adopsi, dan pasangan
- Manajemen relasi dari halaman profil anggota
- Dukungan relasi dengan tanggal mulai dan berakhir (cerai, meninggal)

### Alur Persetujuan

- Antrean approval terpusat di `/approvals`
- Diff view yang jelas: sebelum vs. sesudah perubahan
- Notifikasi real-time ke pemohon saat disetujui/ditolak
- Auto-reject untuk approval yang datanya sudah dihapus

### Notifikasi

- Bell notifikasi di header dengan badge unread count
- Tandai satu atau semua notifikasi sebagai dibaca
- Notifikasi via database dan email

### Ekspor Data

- CSV untuk daftar anggota, relasi, dan statistik
- XLSX untuk daftar anggota
- PDF profil individual (langsung cetak dari browser)

### Manajemen Pengguna (Admin)

- Daftar semua pengguna dengan filter role
- Ubah role pengguna
- Reset password via email
- Tambah pengguna baru berdasarkan data yang sudah ada di tabel anggota

---

## Stack Teknologi

| Layer            | Teknologi                    |
| ---------------- | ---------------------------- |
| Backend          | PHP 8.2+, Laravel 11         |
| Frontend         | Vue 3, Inertia.js v2         |
| Styling          | Tailwind CSS v3              |
| Database         | PostgreSQL                   |
| State Management | Pinia                        |
| Visualisasi      | D3.js v7                     |
| Otorisasi        | Spatie Laravel Permission v6 |
| Icons            | Phosphor Icons               |
| Routing Frontend | Ziggy                        |
| Build Tool       | Vite 6                       |

---

## Prasyarat

Pastikan sistem kamu memiliki:

- **PHP** `>= 8.2` dengan ekstensi: `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`
- **Composer** `>= 2.0`
- **Node.js** `>= 20` dan **npm** `>= 10`
- **PostgreSQL** `>= 14`
- **Git**

---

## Instalasi

### 1. Clone repositori

```bash
git clone https://github.com/garinarka/webtrah.git
cd webtrah
```

### 2. Install dependensi PHP

```bash
composer install
```

### 3. Install dependensi JavaScript

```bash
npm install
```

### 4. Salin file environment

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Konfigurasi database

Edit `.env` dan sesuaikan koneksi PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=webtrah
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 6. Jalankan migrasi dan seeder

```bash
# Buat tabel dan isi data awal
php artisan migrate --seed

# Atau mulai dari awal (hapus semua data)
php artisan migrate:fresh --seed
```

Seeder default membuat:

- Role & permission (admin, moderator, user)
- Akun admin default: `admin@webtrah.com` / `password`
- Akun moderator: `moderator@webtrah.com` / `password`
- Akun user: `user@webtrah.com` / `password`
- Contoh unit keluarga dan beberapa anggota

### 7. Build aset frontend

```bash
# Development (dengan hot reload)
npm run dev

# Production
npm run build
```

### 8. Jalankan server

```bash
php artisan serve
```

Aplikasi tersedia di `http://localhost:8000`.

---

## Konfigurasi

### Email (untuk notifikasi dan reset password)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@webtrah.com
MAIL_FROM_NAME="Webtrah"
```

Untuk pengembangan lokal, gunakan [Mailtrap](https://mailtrap.io) atau [Mailpit](https://mailpit.axllent.org).

### Timezone

```env
APP_TIMEZONE=Asia/Jakarta
```

> **Penting:** Pastikan timezone disetel dengan benar. Webtrah menyimpan tanggal dalam format `Y-m-d` tanpa timezone untuk menghindari pergeseran tanggal saat serialize/deserialize.

---

## Struktur Peran

Webtrah memiliki tiga peran dengan hak akses berbeda:

### Admin

Akses penuh ke seluruh sistem.

| Aksi                   | Keterangan                                 |
| ---------------------- | ------------------------------------------ |
| Tambah anggota         | Langsung aktif, tanpa persetujuan          |
| Edit anggota           | Langsung diterapkan                        |
| Hapus anggota          | Langsung dihapus                           |
| Kelola unit keluarga   | CRUD penuh                                 |
| Kelola pengguna        | Ubah role, reset password, tambah pengguna |
| Setujui/tolak approval | Semua approval dari moderator dan user     |
| Ekspor data            | CSV, XLSX, PDF                             |

### Moderator

Ditugaskan ke satu unit keluarga. Bisa melihat semua data, namun hanya bisa mengubah data di unit keluarganya sendiri.

| Aksi                             | Keterangan                                       |
| -------------------------------- | ------------------------------------------------ |
| Tambah anggota                   | Masuk antrian approval (status: pending)         |
| Edit anggota                     | Masuk antrian approval (perlu persetujuan admin) |
| Hapus anggota                    | Masuk antrian approval                           |
| Setujui/tolak approval dari user | Hanya untuk unitnya                              |
| Melihat semua data               | Read-only untuk unit lain                        |

### User

Hanya bisa mengelola data miliknya sendiri.

| Aksi                    | Keterangan                    |
| ----------------------- | ----------------------------- |
| Melihat semua anggota   | Read-only                     |
| Edit data miliknya      | Masuk antrian approval        |
| Menyimpan draft         | Bisa simpan sebagai draft     |
| Tidak bisa tambah/hapus | Tidak ada akses create/delete |

---

## Alur Kerja Persetujuan

```
Moderator / User mengajukan perubahan
        │
        ▼
  Status: pending
  Masuk antrian /approvals
        │
        ├─── Admin / Moderator (unit sama) meninjau
        │
        ├── SETUJU ──────────────────────────────► Perubahan diterapkan
        │                                          Notifikasi → pemohon
        │
        └── TOLAK ───────────────────────────────► Data tidak berubah
                                                   Notifikasi + alasan → pemohon
```

### Skenario khusus: Draft

Data yang disimpan sebagai draft **tidak masuk** antrian approval dan **tidak tampil** di daftar anggota aktif. Draft hanya bisa dilihat oleh pembuatnya di `/people/drafts`.

Dari halaman draft, klik "Lanjutkan" untuk membuka mode edit draft (`?mode=draft`), lalu pilih:

- **Simpan Draft** — update data, tetap tersimpan sebagai draft
- **Simpan & Aktifkan** (admin) — publikasikan langsung
- **Ajukan ke Admin** (moderator) — kirim ke antrian approval

---

## Struktur Direktori

```
webtrah/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ApprovalController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ExportController.php
│   │   │   ├── FamilyUnitController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── PersonController.php
│   │   │   ├── RelationshipController.php
│   │   │   ├── TreeController.php
│   │   │   └── UserManagementController.php
│   │   ├── Middleware/
│   │   │   └── HandleInertiaRequests.php
│   │   └── Requests/
│   │       ├── StorePersonRequest.php
│   │       └── UpdatePersonRequest.php
│   ├── Models/
│   │   ├── Approval.php
│   │   ├── AuditLog.php
│   │   ├── FamilyUnit.php
│   │   ├── Person.php
│   │   ├── Relationship.php
│   │   └── User.php
│   ├── Notifications/
│   │   ├── ApprovalDecided.php
│   │   └── ApprovalRequested.php
│   ├── Policies/
│   │   ├── ApprovalPolicy.php
│   │   └── PersonPolicy.php
│   └── Services/
│       └── NotificationService.php
│
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── RolePermissionSeeder.php
│
├── resources/
│   └── js/
│       ├── features/
│       │   └── people/
│       │       ├── components/
│       │       │   ├── PersonFormWizard.vue
│       │       │   └── steps/
│       │       │       ├── StepBasicInfo.vue
│       │       │       ├── StepDates.vue
│       │       │       ├── StepFamily.vue
│       │       │       └── StepReview.vue
│       │       └── composables/
│       │           ├── useAutoSave.js
│       │           └── usePersonForm.js
│       ├── layouts/
│       │   └── AppLayout.vue
│       ├── Pages/
│       │   ├── Approvals/
│       │   ├── Dashboard/
│       │   ├── People/
│       │   │   ├── Create.vue
│       │   │   ├── Drafts.vue
│       │   │   ├── Edit.vue
│       │   │   ├── Index.vue
│       │   │   └── Show.vue
│       │   ├── Tree/
│       │   └── Admin/
│       └── stores/
│           ├── auth.js
│           └── ui.js
│
└── routes/
    └── web.php
```

---

## Rute API

Semua rute memerlukan autentikasi (`auth` + `verified`).

### Umum (semua role)

| Method | URI                        | Deskripsi                     |
| ------ | -------------------------- | ----------------------------- |
| `GET`  | `/dashboard`               | Dashboard sesuai peran        |
| `GET`  | `/people`                  | Daftar anggota aktif          |
| `GET`  | `/people/{id}`             | Profil anggota                |
| `GET`  | `/people/drafts`           | Draft milik sendiri           |
| `GET`  | `/tree`                    | Pohon keluarga interaktif     |
| `GET`  | `/family`                  | Daftar unit keluarga          |
| `GET`  | `/notifications`           | List notifikasi (JSON)        |
| `POST` | `/notifications/{id}/read` | Tandai satu notifikasi dibaca |
| `POST` | `/notifications/read-all`  | Tandai semua dibaca           |

### Moderator & Admin

| Method   | URI                       | Deskripsi                  |
| -------- | ------------------------- | -------------------------- |
| `GET`    | `/people/create`          | Form tambah anggota        |
| `POST`   | `/people`                 | Simpan anggota baru        |
| `GET`    | `/people/{id}/edit`       | Form edit anggota          |
| `PATCH`  | `/people/{id}`            | Simpan perubahan           |
| `DELETE` | `/people/{id}`            | Hapus / ajukan penghapusan |
| `GET`    | `/approvals`              | Antrian persetujuan        |
| `GET`    | `/approvals/{id}`         | Detail approval            |
| `POST`   | `/approvals/{id}/approve` | Setujui                    |
| `POST`   | `/approvals/{id}/reject`  | Tolak                      |

### Admin only

| Method     | URI                                | Deskripsi                       |
| ---------- | ---------------------------------- | ------------------------------- |
| `GET/POST` | `/family/create`                   | Buat unit keluarga              |
| `PATCH`    | `/family/{id}`                     | Edit unit keluarga              |
| `DELETE`   | `/family/{id}`                     | Hapus unit keluarga             |
| `GET`      | `/admin/users`                     | Manajemen pengguna              |
| `PATCH`    | `/admin/users/{id}/role`           | Ubah role                       |
| `POST`     | `/admin/users/{id}/reset-password` | Reset password                  |
| `POST`     | `/admin/users/from-person`         | Buat pengguna dari data anggota |
| `GET`      | `/export`                          | Halaman ekspor                  |
| `GET`      | `/export/people/csv`               | Ekspor CSV anggota              |
| `GET`      | `/export/people/xlsx`              | Ekspor XLSX anggota             |

---

## Pengembangan

### Menjalankan test

```bash
php artisan test
```

### Reset database

```bash
php artisan migrate:fresh --seed
```

### Menambah permission baru

Edit `database/seeders/RolePermissionSeeder.php`, tambahkan permission ke array `$permissions`, lalu assign ke role yang sesuai. Jalankan ulang seeder:

```bash
php artisan db:seed --class=RolePermissionSeeder
```

### Konvensi kode

- **PHP:** PSR-12, type hints wajib untuk parameter dan return type
- **Vue:** Composition API dengan `<script setup>`, satu komponen per file
- **Naming:** camelCase untuk JS/Vue, snake_case untuk PHP/DB
- **Tanggal:** Selalu simpan sebagai `Y-m-d` string, hindari timezone conversion

---

## Lisensi

Webtrah dilisensikan di bawah [MIT License](LICENSE).
