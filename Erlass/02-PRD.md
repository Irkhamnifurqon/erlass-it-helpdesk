# Product Requirements Document (PRD)

## Ringkasan

Erlass IT Helpdesk adalah aplikasi internal untuk mencatat, menetapkan, menangani, memantau, dan mengevaluasi permintaan layanan IT.

## Masalah

Permintaan tersebar di berbagai kanal; status dan penanggung jawab tidak transparan; distribusi pekerjaan sulit dipantau; histori penyelesaian dan metrik SLA tidak terpusat.

## Persona

- **Karyawan:** melaporkan kendala, memantau status, memberi konfirmasi dan rating.
- **IT Support:** mengerjakan tiket yang ditugaskan, berkomunikasi, mencatat solusi.
- **Supervisor IT:** memantau antrean, assign/reassign, workload, SLA, laporan.
- **Administrator:** mengelola akun, role, departemen, kategori, prioritas, SLA, konfigurasi.

## Functional Requirements

| ID    | Requirement                                        | Prioritas                  |
| ----- | -------------------------------------------------- | -------------------------- |
| FR-01 | Login/logout, session aman, akun aktif/nonaktif    | Must                       |
| FR-02 | Empat role dan authorization backend               | Must                       |
| FR-03 | CRUD user dan departemen                           | Must                       |
| FR-04 | CRUD kategori dan prioritas                        | Must                       |
| FR-05 | Karyawan membuat tiket dengan nomor unik           | Must                       |
| FR-06 | Daftar, detail, pencarian, filter, pagination      | Must                       |
| FR-07 | Supervisor assign/reassign IT Support              | Must                       |
| FR-08 | Validasi workflow status                           | Must                       |
| FR-09 | Komentar publik dan catatan internal               | Must                       |
| FR-10 | Lampiran privat dan download terotorisasi          | Must                       |
| FR-11 | Histori perubahan status/assignment                | Must                       |
| FR-12 | SLA snapshot per tiket                             | Must                       |
| FR-13 | SLA breach detection dan deadline indicator        | Must                       |
| FR-14 | Notifikasi in-app                                  | Must                       |
| FR-15 | Notifikasi email                                   | Should                     |
| FR-16 | Dashboard berbeda per role                         | Must                       |
| FR-17 | Laporan dengan filter                              | Should                     |
| FR-18 | Ekspor CSV/Excel/PDF                               | Should                     |
| FR-19 | Rating kepuasan                                    | Should                     |
| FR-20 | Pause SLA saat Pending dan business-hours calendar | Should, menunggu kebijakan |

## Non-functional requirements

Security, performance, usability, maintainability, reliability, auditability, backup, privacy, compatibility browser modern, responsive desktop/mobile.

## Acceptance Criteria

- **AC-01:** tiket valid menghasilkan nomor unik, status Open, SLA snapshot, history.
- **AC-02:** karyawan tidak dapat membaca tiket milik orang lain, termasuk melalui URL langsung.
- **AC-03:** assignment hanya dapat dilakukan Supervisor/Admin ke IT Support aktif; assignment lama ditutup.
- **AC-04:** transisi status ilegal ditolak dan setiap perubahan tercatat.
- **AC-05:** internal note dan lampirannya tidak terlihat/terunduh oleh karyawan.
- **AC-06:** perubahan SLA policy tidak mengubah snapshot tiket lama.
- **AC-07:** status Resolved mewajibkan solusi dan mencatat waktu.
- **AC-08:** hanya pemohon yang dapat mengonfirmasi/menilai tiketnya sesuai aturan.

## KPI

Ticket volume, backlog, first response time, resolution time, SLA compliance, reopen rate, satisfaction average, workload per agent. Formula KPI harus didefinisikan konsisten sebelum laporan menjadi laporan resmi.
