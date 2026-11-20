# Decisions and Open Questions

## Baseline decisions

| Topik               | Keputusan                                          |
| ------------------- | -------------------------------------------------- |
| Nama                | Erlass IT Helpdesk                                 |
| Organisasi          | Erlass Proaktif Indonesia                          |
| Backend             | Laravel                                            |
| Frontend            | Blade + Bootstrap 5                                |
| Database            | MySQL                                              |
| Roles               | Administrator, Supervisor IT, IT Support, Karyawan |
| Assignment          | Supervisor memilih IT Support                      |
| Approval            | Tidak ada approval                                 |
| SLA basis           | Kategori, bukan prioritas                          |
| Notifications       | In-app dan email                                   |
| Hosting awal        | Server lokal perusahaan                            |
| UI                  | Responsive desktop/mobile                          |
| Support team        | Banyak IT Support                                  |
| Employee visibility | Hanya tiket sendiri                                |

## Wajib dikonfirmasi perusahaan

### SLA

1. Hari dan jam kerja resmi?
2. Apakah akhir pekan dihitung?
3. Hari libur nasional/perusahaan?
4. SLA kalender atau business minutes?
5. Apakah Pending menjeda response, resolution, atau keduanya?
6. Apakah target contoh per kategori disetujui?
7. Definisi first response?

### Workflow

8. Apakah karyawan wajib menutup tiket setelah Resolved?
9. Berapa lama tiket Closed dapat dibuka kembali?
10. Apakah Supervisor boleh override status?
11. Apakah IT Support dapat mengembalikan tiket?
12. Apakah assignment dapat lintas departemen?

### User dan infrastruktur

13. Akun dibuat Admin atau self-registration?
14. Apakah email perusahaan wajib?
15. Perlu SSO/Active Directory/Google Workspace?
16. Struktur departemen dan lokasi kantor?
17. SMTP provider?
18. Spesifikasi server dan domain?
19. Backup dan retensi file?
20. Perlu antivirus scanning attachment?

### Reporting

21. Periode laporan standar?
22. Definisi produktivitas IT Support?
23. Target SLA compliance?
24. Apakah laporan perlu persetujuan manajemen?

## Change control

Setiap perubahan role, workflow, SLA, atau schema harus dicatat beserta alasan, dampak, update dokumen, test, dan persetujuan pemilik produk.
