# Erlass IT Helpdesk

Panduan pengembangan dan pengoperasian aplikasi **Erlass IT Helpdesk** berbasis Laravel, React/Inertia, dan MySQL.

## Ringkasan Aplikasi

Aplikasi menyediakan:

- Pembuatan tiket dengan kategori, jenis kendala, prioritas, dan lampiran.
- Opsi kategori dan jenis kendala **Lainnya**.
- Lampiran foto, PDF, dokumen, spreadsheet, dan video sampai 50 MB.
- Penugasan tiket kepada petugas IT.
- Status tiket, solusi, percakapan, lampiran percakapan, dan riwayat perubahan.
- Perpanjangan SLA beserta alasan dan riwayat.
- Status Overdue, penutupan otomatis tiket resolved setelah 3 hari.
- Pilihan pelapor untuk menutup atau membuka kembali tiket.
- Rating kinerja petugas oleh pelapor.
- Dashboard, laporan, grafik ringkas, pencarian, dan unduhan PDF/Excel.
- Unduhan PDF untuk satu tiket atau rekap seluruh tiket.

## Teknologi

| Komponen | Teknologi |
| --- | --- |
| Backend | PHP 8.3, Laravel 13 |
| Frontend | React 19, TypeScript, Inertia.js, Tailwind CSS |
| Database | MySQL/MariaDB |
| PDF | Dompdf 3.1.6 |
| Development server | Laravel Artisan, Vite Plus |

## Persyaratan Windows

- PHP 8.3 atau lebih baru.
- Composer 2.
- Node.js dan npm.
- MySQL atau MariaDB.
- XAMPP atau Laragon untuk MySQL.
- Ekstensi PHP:
  - `pdo_mysql`
  - `mbstring`
  - `openssl`
  - `fileinfo`
  - `gd`
  - `xml`
  - `ctype`
  - `curl`

Aktifkan ekstensi dengan membuka `php.ini`, pastikan baris berikut tidak diawali tanda `;`:

```ini
extension=pdo_mysql
extension=mbstring
extension=fileinfo
extension=gd
extension=xml
extension=ctype
extension=curl
```

Untuk mengizinkan lampiran video maksimal 50 MB, gunakan:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_file_uploads = 20
```

## Instalasi Pertama

Masuk ke folder proyek:

```powershell
cd D:\1Belajar-Erlass\tiket
```

1. Instal dependency PHP:

```powershell
composer install
```

2. Buat konfigurasi environment:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

3. Buat database MySQL:

```sql
CREATE DATABASE erlass_tiket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Sesuaikan `.env`:

```ini
APP_NAME="Erlass IT Helpdesk"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erlass_tiket
DB_USERNAME=root
DB_PASSWORD=
```

5. Jalankan migration dan seeder:

```powershell
php artisan migrate
php artisan db:seed
```

6. Instal dependency frontend:

```powershell
npm install
```

## Menjalankan Aplikasi

Terminal 1:

```powershell
php artisan serve
```

Terminal 2:

```powershell
npm run dev
```

Buka:

```text
http://localhost:8000
```

Untuk menjalankan frontend dan backend sekaligus, tersedia juga:

```powershell
composer run dev
```

## Akun Awal dari Seeder

Pastikan seeder dijalankan kembali setelah perubahan database.

| Role | Email | Password |
| --- | --- | --- |
| Administrator IT | `admin@erlass.test` | `password` |
| Karyawan | `user@erlass.test` | `password` |

Ganti password akun bawaan sebelum aplikasi dipakai produksi.

## Aturan Role

- **Administrator**: mengelola tiket, menugaskan petugas, mengubah status, memperpanjang SLA, dan membuka laporan.
- **Petugas IT**: harus memiliki role Administrator serta departemen IT agar dapat ditugaskan sebagai petugas.
- **Karyawan**: membuat tiket, berkomunikasi, menutup atau membuka kembali tiket, dan memberi rating.

## Alur Status Tiket

Status utama:

```text
open -> assigned -> in_progress -> resolved -> closed
                              |
                              +-> pending_user
                              +-> on_hold
                              +-> reopened
```

Aturan tambahan:

- Pelapor dapat memilih **Ditutup** atau **Dibuka kembali** setelah status resolved.
- Tiket resolved ditutup otomatis setelah 3 hari bila tidak ada keputusan pelapor.
- Perubahan status, percakapan, penugasan, rating, dan perpanjangan SLA tercatat pada history tiket.

## Perhitungan SLA dan Overdue

SLA ditentukan dari policy kategori. Pada pembuatan tiket:

- Target pertama: `response_target_minutes`.
- Target penyelesaian: `resolution_target_minutes`.
- Waktu dihitung sejak tiket dibuat.
- Overdue aktif ketika tanggal target penyelesaian telah terlewati dan tiket belum resolved/closed.
- Petugas Administrator dapat memperpanjang target penyelesaian 1–30 hari beserta alasan.

Jalankan pemeriksaan overdue manual:

```powershell
php artisan tickets:notify-overdue
```

Jalankan penutupan otomatis manual:

```powershell
php artisan tickets:auto-close-resolved
```

Untuk produksi, Windows Task Scheduler atau proses cron setara harus menjalankan:

```powershell
php artisan schedule:work
```

Laravel Scheduler menjalankan:

- Notifikasi overdue setiap jam.
- Penutupan resolved setiap hari.
- Pembersihan undangan tim setiap hari.

## Lampiran

- Maksimum 5 lampiran setiap pengiriman.
- Ukuran maksimum per lampiran 50 MB.
- Format yang diterima: JPG, JPEG, PNG, GIF, WebP, PDF, DOC, DOCX, XLS, XLSX, TXT, MP4, MOV, dan WebM.
- Lampiran disimpan secara privat pada `storage/app/private/ticket-attachments`.
- Lampiran hanya dapat diakses melalui endpoint detail tiket yang telah melewati authorization policy.

## Unduhan PDF dan Excel

- Detail satu tiket dapat diunduh dari tombol **Unduh tiket PDF** pada halaman detail.
- Admin dapat mengunduh rekap seluruh tiket dari halaman **Tiket Helpdesk**.
- Admin dapat mengunduh laporan periode di menu **Laporan**.
- PDF menggunakan Dompdf dan membutuhkan PHP GD untuk memuat logo PNG.
- Excel dibundel sebagai dokumen HTML berformat `.xls` dengan header berwarna.

## Logo dan Branding

Logo aktif berada pada:

```text
public/images/erlass-logo.png
```

Nama aplikasi dapat diubah pada:

```text
.env -> APP_NAME
config/app.php -> name
```

Komponen logo:

```text
resources/js/components/app-logo.tsx
resources/js/layouts/auth/auth-simple-layout.tsx
```

## Struktur Penting

```text
app/
  Console/Commands/          Perintah overdue dan penutupan otomatis
  Http/Controllers/          Controller Laravel
  Models/                    Model tiket, SLA, lampiran, history, dan rating
  Notifications/             Notifikasi database
  Policies/                  Authorization akses tiket

database/
  migrations/                Struktur database
  seeders/                   Data awal aplikasi

resources/js/
  components/                Komponen React dan sidebar
  layouts/                   Layout auth dan aplikasi
  pages/                     Halaman dashboard, tiket, laporan, dan auth

routes/
  web.php                    Rute aplikasi
  console.php                Scheduler

public/images/
  erlass-logo.png            Logo Erlass
```

## Pemeriksaan Sebelum Commit

Frontend:

```powershell
npm run check
npm run types:check
npm run build
```

PHP dan Laravel:

```powershell
php artisan route:list
php artisan migrate:status
php artisan test
composer run lint:check
```

Untuk format otomatis:

```powershell
npm run check:fix
composer run lint
```

## Troubleshooting

### MySQL aktif tetapi aplikasi tidak tersambung

1. Jalankan MySQL XAMPP atau Laragon.
2. Pastikan database `erlass_tiket` sudah ada.
3. Periksa `DB_HOST`, `DB_PORT`, `DB_USERNAME`, dan `DB_PASSWORD`.
4. Jalankan:

```powershell
php artisan config:clear
php artisan migrate:status
```

### Lampiran video gagal

1. Pastikan PHP CLI dan server memakai `php.ini` yang sama.
2. Pastikan `upload_max_filesize = 50M`.
3. Pastikan `post_max_size = 55M`.
4. Pastikan ekstensi `fileinfo` aktif.
5. Restart PHP/laravel server setelah mengubah `php.ini`.

### Logo atau gambar PDF tidak muncul

Aktifkan ekstensi GD:

```ini
extension=gd
```

Periksa logo:

```text
public/images/erlass-logo.png
```

### Error dependency JavaScript

Hapus install lama dan pasang ulang:

```powershell
Remove-Item -Recurse -Force node_modules
Remove-Item -Force package-lock.json
npm install
npm run build
```

### Error format atau lint

```powershell
npm run check:fix
npm run types:check
```

## Produksi

Untuk lingkungan produksi:

```powershell
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-produksi
```

Jalankan build dan cache:

```powershell
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Pastikan:

- Folder `storage` dan `bootstrap/cache` dapat ditulis oleh service web.
- SSL dan domain sudah dikonfigurasi.
- Database memiliki backup berkala.
- Scheduler dijalankan 24/7.
- Akun awal dan password produksi sudah diganti.
