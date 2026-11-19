# Routes and API Contract

MVP boleh memakai Laravel web routes + Blade form POST. Kontrak berikut bersifat konseptual.

## Authentication
GET/POST `/login`; POST `/logout`.

## Tickets
GET `/tickets` — daftar scoped.
GET `/tickets/create` — form.
POST `/tickets` — buat.
GET `/tickets/{ticket}` — detail dengan Policy.
POST `/tickets/{ticket}/assignments` — Supervisor/Admin.
POST `/tickets/{ticket}/status` — aktor berwenang.
POST `/tickets/{ticket}/comments` — aktor berwenang.
POST `/tickets/{ticket}/attachments` — upload.
GET `/tickets/{ticket}/attachments/{attachment}` — download terproteksi.
POST `/tickets/{ticket}/rating` — pemohon.
GET `/tickets/{ticket}/history` — aktor berwenang.

## Admin
`/admin/users`, `/admin/roles`, `/admin/departments`, `/admin/categories`, `/admin/priorities`, `/admin/sla-policies`.

## Reports
`/reports/tickets`, `/reports/sla`, `/reports/technicians`, `/reports/satisfaction`, `/reports/export/{format}`.

## Validasi kontrak
- Assignment target wajib user aktif ber-role IT Support; assigner dari auth session.
- Status harus transisi valid; solusi wajib untuk Resolved; alasan wajib untuk Pending/Reopened.
- `is_internal` hanya dapat diatur staf internal.
- Rating hanya pemohon dan score 1–5.
- Seluruh endpoint detail, histori, dan attachment menerapkan Policy.
