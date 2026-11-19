# Windows Deployment Guide

## Prasyarat
Windows 10/11, PHP CLI kompatibel, Composer 2, MySQL/MariaDB, Git, VS Code. PHP extensions minimal: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo, curl.

## Audit environment (PowerShell)
```powershell
php -v
php --ini
php -m
composer --version
where.exe php
where.exe composer
```
Pastikan PHP CLI yang aktif adalah versi yang dimaksud.

## Buat proyek
```powershell
composer create-project laravel/laravel erlass-it-helpdesk
cd erlass-it-helpdesk
```
Pilih versi Laravel setelah memeriksa kompatibilitas PHP dan paket.

## Database
Buat database melalui phpMyAdmin atau MySQL client:
```sql
CREATE DATABASE erlass_helpdesk
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```
SQL dijalankan di phpMyAdmin/MySQL client, bukan langsung di PowerShell.

## Environment
Atur `.env` untuk APP_NAME, APP_URL, DB_CONNECTION=mysql, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, timezone Asia/Jakarta.

## Install
```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan test
php artisan serve
```
Lampiran harus menggunakan private disk dan tidak boleh dapat diakses langsung dari public web root.

## Production
APP_DEBUG=false, HTTPS, database user least privilege, SMTP resmi, queue worker, scheduler, backup database/file, log rotation, monitoring disk, restore drill. Akun demo hanya untuk development dan password wajib diganti.
