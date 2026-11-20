# Project Charter

## Identitas

- Nama: Erlass IT Helpdesk
- Organisasi: Erlass Proaktif Indonesia
- Jenis: aplikasi web internal IT Service Desk
- Bahasa: Indonesia
- Zona waktu tampilan: Asia/Jakarta
- Teknologi baseline: Laravel, MySQL, Blade, Bootstrap 5, JavaScript, Chart.js
- Hosting awal: server lokal perusahaan

## Latar belakang

Permintaan bantuan IT perlu dikelola terpusat agar setiap masalah memiliki nomor tiket, pemilik, status, histori, penugasan, komunikasi, dan target layanan yang dapat dipantau.

## Tujuan

1. Menyediakan kanal resmi pelaporan masalah IT.
2. Memastikan setiap tiket dapat ditelusuri.
3. Memudahkan Supervisor IT membagi pekerjaan.
4. Membantu IT Support mengelola pekerjaan dan SLA.
5. Memberikan transparansi status kepada karyawan.
6. Menghasilkan laporan layanan dan kepuasan.

## Stakeholder

Karyawan, IT Support, Supervisor IT, Administrator, dan manajemen.

## Ruang lingkup MVP

Login, role access, user/departemen, kategori/prioritas, ticket lifecycle, assignment/reassignment, komentar publik/internal, lampiran privat, histori, SLA snapshot, notifikasi in-app/email, dashboard per role, laporan dasar, ekspor, rating, audit tindakan penting.

## Di luar MVP kecuali disetujui

SSO/AD, integrasi WhatsApp/Teams, asset management/CMDB, remote desktop, AI chatbot/auto-classification, aplikasi native mobile, multi-tenant.

## Asumsi

- Semua karyawan dapat membuat tiket.
- Ada beberapa IT Support.
- Supervisor IT memilih petugas.
- Tidak ada approval sebelum tiket ditangani.
- SLA berdasarkan kategori, bukan prioritas.
- Aplikasi awal berjalan pada server lokal.
- SMTP dan detail server produksi disediakan perusahaan.

## Definition of Done

Fitur selesai jika backend dan UI tersedia, authorization server-side diterapkan, migration fresh install berhasil, test terkait lulus, dokumentasi diperbarui, dan tidak ada credential di repository.
