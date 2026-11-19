# Business Workflows

## Alur tiket
1. Karyawan membuat tiket.
2. Sistem menghasilkan nomor unik, status Open, SLA snapshot, history, dan notifikasi.
3. Supervisor meninjau antrean dan memilih IT Support.
4. Status menjadi Assigned.
5. IT Support mulai: In Progress.
6. Jika menunggu informasi/pihak lain: Pending dengan alasan.
7. Setelah siap, kembali In Progress.
8. Petugas mengisi solusi dan mengubah ke Resolved.
9. Karyawan mengonfirmasi Closed atau Reopened dengan alasan.

## Transisi status yang diizinkan
| Dari | Ke | Aktor | Ketentuan |
|---|---|---|---|
| Open | Assigned | Supervisor/Admin | IT Support aktif dipilih |
| Assigned | In Progress | Petugas aktif | Assignment aktif |
| In Progress | Pending | Petugas aktif | Alasan wajib |
| Pending | In Progress | Petugas aktif | Pekerjaan dilanjutkan |
| In Progress | Resolved | Petugas aktif | Solusi wajib |
| Pending | Resolved | Petugas aktif | Solusi wajib |
| Resolved | Closed | Pemohon | Konfirmasi |
| Resolved/Closed | Reopened | Pemohon | Alasan wajib |
| Reopened | In Progress | Petugas aktif | Assignment aktif |

Override Supervisor/Admin harus eksplisit dan dicatat. Transisi lainnya ditolak.

## Assignment
- Hanya Supervisor/Admin.
- Target harus user aktif ber-role IT Support.
- Tutup assignment lama (`unassigned_at`) sebelum membuat assignment baru.
- Satu assignment aktif per tiket.
- Catat assigner, assignee, waktu, dan history.

## Komentar dan lampiran
Komentar internal tidak boleh muncul pada tampilan/API karyawan. Lampiran komentar internal mengikuti visibility komentar. Download selalu melalui controller terotorisasi.

## History event minimum
created, assigned, reassigned, status_changed, comment_added, attachment_added, solution_added, reopened, closed, rating_submitted, sla_breached, admin_override.
History operasional bersifat append-only bagi pengguna aplikasi.
