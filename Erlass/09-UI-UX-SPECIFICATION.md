# UI/UX Specification

## Visual direction

Modern enterprise dashboard; navy blue sebagai warna utama, white/light gray untuk surface, aksen blue, Bootstrap 5, Bootstrap Icons, system font/Inter.

## Layout

Desktop: sidebar kiri, topbar, breadcrumb, konten.
Mobile: sidebar offcanvas/collapsible, tabel scroll atau card list, form satu kolom.

## Halaman

1. Login.
2. Dashboard per role.
3. Daftar tiket: search, filter, sort, pagination.
4. Buat tiket: judul, kategori, prioritas, deskripsi, lampiran.
5. Detail tiket: informasi, SLA, assignment, percakapan, lampiran, histori, status.
6. User management.
7. Department management.
8. Category management.
9. Priority management.
10. SLA policy management.
11. Reports and exports.
12. Notification center.

## UX rules

- Nomor tiket tampil jelas setelah create.
- Konfirmasi sebelum tindakan berdampak.
- Tombol disesuaikan role, tetapi backend tetap memvalidasi.
- Status ditampilkan dengan teks dan badge, bukan warna saja.
- Empty state dan error/success feedback tersedia.
- Waktu ditampilkan Asia/Jakarta.
- Form validasi mudah dipahami.
- Responsive desktop/tablet/mobile.
