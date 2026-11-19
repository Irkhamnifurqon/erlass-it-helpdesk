# Roles and Permissions

## Role
Administrator, Supervisor IT, IT Support, Karyawan.

| Kapabilitas | Admin | Supervisor | IT Support | Karyawan |
|---|---:|---:|---:|---:|
| Lihat semua tiket | Ya | Ya | Tidak | Tidak |
| Lihat tiket sendiri | Ya | Ya | Jika requester | Ya |
| Lihat tiket ditugaskan | Ya | Ya | Ya | Tidak |
| Membuat tiket | Ya | Ya | Sesuai kebijakan | Ya |
| Assign/reassign | Ya | Ya | Tidak | Tidak |
| Update status pekerjaan | Override | Monitoring/override | Tiket aktif | Tidak |
| Reopen tiket sendiri | Ya | Ya | Tidak | Ya |
| Komentar publik | Ya | Ya | Tiket berhak akses | Tiket sendiri |
| Internal note | Ya | Ya | Tiket berhak akses | Tidak |
| Kelola user/role | Ya | Tidak default | Tidak | Tidak |
| Kelola kategori/prioritas/SLA | Ya | Tidak default | Tidak | Tidak |
| Laporan global | Ya | Ya | Tidak | Tidak |
| Rating | Tidak sebagai requester | Tidak sebagai requester | Tidak | Tiket sendiri |

Catatan:
- Karyawan hanya melihat tiket dengan `requester_id = auth()->id()`.
- IT Support hanya melihat tiket dengan assignment aktif kepadanya, kecuali akses historis diberikan eksplisit.
- Admin/Supervisor dapat melihat semua tiket.
- Default deny; backend policy wajib memeriksa akses.
- Menyembunyikan tombol di UI bukan pengganti authorization backend.
- Internal comments dan attachment turunannya hanya untuk staf internal berwenang.
