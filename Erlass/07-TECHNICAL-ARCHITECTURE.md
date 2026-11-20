# Technical Architecture

## Stack

Laravel (versi dipilih setelah cek kompatibilitas PHP), MySQL, Blade, Bootstrap 5, JavaScript, Chart.js, Laravel Notifications, Queue dan Scheduler.

## Request flow

Browser → Web Route → Auth/Middleware → Form Request → Controller → Service/Action → Eloquent → MySQL.

File download: route terproteksi → Policy/access check → private storage.

Notification: business event → Notification service/Laravel Notification → database dan mail.

Scheduled task: Laravel Scheduler → scan SLA/reminder → notification.

## Prinsip

- Controller tipis.
- Validasi di Form Request.
- Authorization di Policy/Gate.
- Business logic di Service/Action.
- Transaction untuk operasi multi-tabel.
- Event/listener untuk side effect bila diperlukan.
- Status terpusat dalam Enum/constant.
- Eager loading dan pagination untuk mencegah N+1.
- Logging tanpa credential atau data sensitif.

## Struktur

`app/Actions`, `app/Enums`, `app/Events`, `app/Http/Controllers`, `app/Http/Requests`, `app/Models`, `app/Notifications`, `app/Policies`, `app/Services`, `database/migrations`, `database/seeders`, `resources/views`, `routes`, `tests/Feature`, `tests/Unit`.

## Concurrency

Ticket number menggunakan sequence table dengan lock/transaksi atau strategi atomik. Assignment aktif dan perubahan status perlu row lock/constraint yang sesuai agar race condition tidak menghasilkan data ganda atau transisi konflik.

## Observability

Logging error, failed queue, health check, audit event penting, backup/restore test.
