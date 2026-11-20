# Database Schema

## Konvensi

MySQL InnoDB, utf8mb4, BIGINT UNSIGNED PK (kecuali UUID notifications), timestamps, foreign key, index. Simpan waktu konsisten (disarankan UTC) dan tampilkan Asia/Jakarta.

## Tabel

### departments

`id PK`, `name VARCHAR(120) UNIQUE NOT NULL`, `code VARCHAR(20) UNIQUE NULL`, timestamps.

### roles

`id PK`, `name VARCHAR(60) UNIQUE NOT NULL`, `description VARCHAR(255) NULL`, timestamps.
Nilai: Administrator, Supervisor IT, IT Support, Karyawan.

### users

`id PK`, `department_id FK NULL`, `name VARCHAR(150)`, `email VARCHAR(190) UNIQUE`, `email_verified_at NULL`, `password VARCHAR(255) HASHED`, `is_active BOOLEAN DEFAULT true`, `remember_token NULL`, timestamps.

### user_roles

`user_id FK`, `role_id FK`, composite PK (`user_id`,`role_id`).

### categories

`id PK`, `name VARCHAR(100) UNIQUE`, `description TEXT NULL`, `is_active BOOLEAN`, timestamps.
Nilai awal: Hardware, Software, Network, Account, Website, Email, Access, Other.

### priorities

`id PK`, `name VARCHAR(30) UNIQUE`, `level TINYINT UNIQUE`, `sort_order TINYINT`, `color VARCHAR(20)`, timestamps.
Nilai: Low=1, Medium=2, High=3, Critical=4.

### sla_policies

`id PK`, `category_id FK UNIQUE` (MVP one policy/category), `response_target_minutes INT`, `resolution_target_minutes INT`, `is_active BOOLEAN`, timestamps.
Jika perlu policy versioning/effective dates, tambahkan `effective_from`, `effective_until` dan mekanisme versi.

### tickets

`id PK`, `ticket_number VARCHAR(30) UNIQUE`, `requester_id FK users`, `category_id FK`, `priority_id FK`, `title VARCHAR(180)`, `description TEXT`, `status VARCHAR(30) INDEX`, `solution TEXT NULL`, `resolved_at NULL`, `closed_at NULL`, timestamps.
Format nomor: `ERL-YYYY-00001`. Gunakan sequence/counter table dengan transaction + row lock atau generator atomik lain. Jangan memakai `COUNT(*) + 1`.

### ticket_assignments

`id PK`, `ticket_id FK`, `assigned_to FK users`, `assigned_by FK users`, `assigned_at`, `unassigned_at NULL`, timestamps.
Index `(ticket_id, unassigned_at)` dan `(assigned_to, unassigned_at)`. Pastikan paling banyak satu assignment aktif per tiket.

### ticket_comments

`id PK`, `ticket_id FK`, `user_id FK`, `body TEXT`, `is_internal BOOLEAN DEFAULT false`, timestamps.

### ticket_attachments

`id PK`, `ticket_id FK`, `comment_id FK NULL`, `user_id FK`, `original_name VARCHAR(255)`, `stored_path VARCHAR(500)`, `mime_type VARCHAR(150) NULL`, `size BIGINT`, timestamps.

### ticket_histories

`id PK`, `ticket_id FK`, `user_id FK NULL`, `event VARCHAR(50)`, `old_value JSON NULL`, `new_value JSON NULL`, `description TEXT NULL`, timestamps.

### ticket_slas

`id PK`, `ticket_id FK UNIQUE`, `response_target_minutes INT`, `resolution_target_minutes INT`, `response_due_at NULL`, `resolution_due_at NULL`, `first_response_at NULL`, `resolved_at NULL`, `paused_at NULL`, `total_paused_minutes INT DEFAULT 0`, timestamps.

### ticket_ratings

`id PK`, `ticket_id FK UNIQUE`, `user_id FK`, `score TINYINT CHECK 1..5`, `feedback TEXT NULL`, timestamps.

### notifications

Gunakan Laravel database notifications: UUID `id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, timestamps.

## Relasi

Department 1:N Users; User N:M Roles; User 1:N Tickets (requester); Category 1:N Tickets; Priority 1:N Tickets; Category 1:1 SlaPolicy pada MVP; Ticket 1:N Assignments/Comments/Attachments/Histories; Ticket 1:1 TicketSla; Ticket 1:0..1 TicketRating.

## Index minimum

users.email UNIQUE; tickets.ticket_number UNIQUE; tickets(requester_id,created_at); tickets(status,created_at); tickets(category_id,status); tickets(priority_id,status); ticket_assignments(ticket_id,unassigned_at); ticket_assignments(assigned_to,unassigned_at); ticket_comments(ticket_id,created_at); ticket_histories(ticket_id,created_at); ticket_slas(response_due_at); ticket_slas(resolution_due_at).

## Integritas

Nonaktifkan user alih-alih menghapus jika sudah punya histori. Pertahankan assignment/history. Perubahan policy tidak boleh mengubah SLA snapshot lama. Hindari cascade delete pada audit data yang harus dipertahankan.
