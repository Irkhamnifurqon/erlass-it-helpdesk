# SLA Specification

## Prinsip
SLA berdasarkan kategori, bukan prioritas pada baseline awal.

## Target contoh (belum disahkan perusahaan)
| Kategori | Response | Resolution |
|---|---:|---:|
| Hardware | 60 menit | 480 menit |
| Software | 60 menit | 480 menit |
| Network | 30 menit | 240 menit |
| Account | 60 menit | 240 menit |
| Website | 30 menit | 480 menit |
| Email | 60 menit | 240 menit |
| Access | 120 menit | 480 menit |
| Other | 120 menit | 480 menit |

## Snapshot
Saat tiket dibuat, salin response/resolution target dan deadline ke `ticket_slas`. Perubahan policy tidak mengubah tiket lama.

## Definisi
First response time = tiket dibuat sampai respons substantif pertama dari petugas kepada pemohon. Assignment otomatis tidak dihitung sebagai respons. Resolution time = tiket dibuat sampai pertama kali Resolved. Definisi ini perlu disetujui pemilik proses.

Response breach jika respons belum ada setelah due atau respons terjadi sesudah due. Resolution breach jika belum Resolved setelah due.

## Pending
Sebelum implementasi pause, perusahaan harus memutuskan apakah Pending menjeda response clock, resolution clock, atau keduanya; alasan wajib; kapan pause dimulai; dan apakah ada batas maksimum. Simpan pause timestamp dan total duration.

## Business hours
Target angka contoh dihitung sebagai menit kalender untuk MVP/test. Untuk operasional resmi, tetapkan hari kerja, jam kerja, istirahat, hari libur, timezone, dan perlakuan tiket di luar jam kerja.

## Reminder
Reminder mendekati deadline dan breach harus configurable serta idempotent agar tidak mengirim duplikat berulang.
