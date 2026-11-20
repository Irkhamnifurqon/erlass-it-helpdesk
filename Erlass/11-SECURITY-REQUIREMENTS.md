# Security Requirements

## Authentication

Password hashing Laravel, login rate limit, session regenerate, logout invalidate, akun nonaktif ditolak.

## Authorization

Default deny; gunakan Policy/Gate; query scope per requester/assignment; cegah IDOR; jangan percaya role/user_id dari request; semua endpoint dan file download diverifikasi di server.

## Internal notes

Internal comment dan attachment turunannya hanya staf berwenang. Tidak boleh bocor pada response karyawan, dashboard, email, atau notifikasi.

## File

Private storage, random filename, validasi extension/MIME/size, jangan percaya MIME dari client, larang executable/script, download melalui controller terotorisasi, pertimbangkan antivirus scanning production.

## Web

CSRF, Blade escaping, server-side validation, parameter binding, secure cookies/HTTPS, security headers, APP_DEBUG=false production.

## Data

Jangan commit `.env`; jangan log password/token; backup DB dan private files; database user least privilege; tetapkan retensi data.

## Audit

Catat assignment, status, perubahan SLA, role, aktivasi user, admin override, dan tindakan sensitif lain.

## Release checklist

HTTPS aktif, credential unik, backup restore teruji, authorization tests lulus, private storage tidak public, dependency audit dilakukan.
