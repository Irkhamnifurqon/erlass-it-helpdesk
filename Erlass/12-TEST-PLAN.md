# Test Plan

## Strategi
Unit tests untuk state machine, SLA, ticket number. Feature tests untuk auth, authorization, CRUD tiket, assignment, comments, attachments, notifications. Integration tests untuk MySQL, storage, mail, queue. UAT untuk empat role.

## Test cases
| ID | Skenario | Expected |
|---|---|---|
| T-01 | Login valid/invalid | Berhasil/ditolak |
| T-02 | Akun nonaktif login | Ditolak |
| T-03 | Karyawan membuat tiket valid | Tiket + nomor unik + SLA + history |
| T-04 | Tiket tanpa field wajib | Validasi gagal |
| T-05 | Karyawan akses tiket orang lain | 403/404 |
| T-06 | IT Support akses tiket tanpa assignment | 403/404 |
| T-07 | Supervisor lihat seluruh tiket | Berhasil |
| T-08 | Assign ke IT Support aktif | Assignment dan history dibuat |
| T-09 | Assign user bukan IT Support | Ditolak |
| T-10 | Reassign | Assignment lama ditutup |
| T-11 | Transisi status ilegal | Ditolak |
| T-12 | Resolved tanpa solusi | Ditolak |
| T-13 | Reopen tanpa alasan | Ditolak |
| T-14 | Karyawan lihat internal note | Tidak terlihat |
| T-15 | Karyawan unduh internal attachment | Ditolak |
| T-16 | Upload file valid/tidak valid | Valid diterima, invalid ditolak |
| T-17 | SLA snapshot | Target tersalin |
| T-18 | Policy berubah | Snapshot lama tetap |
| T-19 | Nomor tiket concurrent | Tidak duplikat |
| T-20 | Rating oleh pemohon | Berhasil |
| T-21 | Rating oleh user lain | Ditolak |
| T-22 | Dashboard per role | Data scoped benar |
| T-23 | Export report | Sesuai filter dan permission |

## UAT
Karyawan: login, create, upload, comment, track, confirm, rate.
Supervisor: queue, assign/reassign, workload, SLA.
IT Support: assigned list, start, pending, comment, solution, resolve.
Admin: users, departments, category, priority, SLA, reports.

## Exit criteria
Semua test Must lulus, tidak ada critical/high security issue terbuka, migration fresh install berhasil, UAT disetujui, dan instalasi diuji di Windows bersih.
