# Zoom Scheduler

Zoom Scheduler adalah aplikasi Laravel untuk mengelola banyak akun Zoom, membuat meeting dari web atau Telegram, menerima callback/webhook Zoom, menyinkronkan jadwal yang dibuat langsung dari Zoom, dan mengirim notifikasi ke Telegram.

## Fitur Utama

- Multi akun Zoom per user.
- Membuat meeting instan dan meeting terjadwal.
- Pilihan akun otomatis untuk menghindari jadwal bentrok.
- Sinkronisasi meeting dari event Zoom Marketplace.
- Status meeting di `/meetings`: sedang berlangsung, mendatang, selesai, dan rekaman.
- Callback/webhook Zoom per akun dengan Secret Token masing-masing.
- Notifikasi Telegram ke semua chat Telegram yang tertaut ke user.
- Pilihan notifikasi Telegram per event callback untuk setiap akun Zoom.
- Tombol tes callback dari halaman pengaturan.

## Prasyarat

Pastikan sudah tersedia:

- PHP sesuai versi Laravel project.
- Composer.
- Node.js dan npm.
- MySQL/MariaDB, misalnya lewat Laragon.
- Akun Zoom dengan akses Zoom App Marketplace.
- Bot Telegram dari BotFather, jika ingin memakai integrasi Telegram.
- URL publik HTTPS untuk callback, misalnya dari ngrok atau domain server.

## Instalasi Lokal

Clone project, lalu jalankan:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zoom_scheduler
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `zoom_scheduler` di Laragon/phpMyAdmin/Adminer, lalu jalankan:

```bash
php artisan migrate
```

Jalankan aplikasi:

```bash
php artisan serve
npm run dev
```

Biasanya URL lokal Laravel:

```text
http://127.0.0.1:8000
```

Vite frontend:

```text
http://localhost:5173
```

## APP_URL dan URL Publik

Untuk login Zoom OAuth dan callback Zoom, `APP_URL` harus sesuai URL yang bisa diakses browser/Zoom.

Untuk lokal biasa:

```env
APP_URL=http://127.0.0.1:8000
```

Untuk callback Zoom Marketplace, gunakan HTTPS publik:

```env
APP_URL=https://nama-domain-atau-ngrok.ngrok-free.dev
```

Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
```

Catatan penting: jika `APP_URL` masih `localhost` atau `127.0.0.1`, Zoom tidak bisa memvalidasi webhook/callback.

## Setup Akun Zoom

1. Login ke aplikasi.
2. Buka halaman tambah akun Zoom.
3. Di Zoom App Marketplace, buat app bertipe General App/OAuth sesuai instruksi pada halaman aplikasi.
4. Isi OAuth Redirect URL:

```text
{APP_URL}/zoom/callback
```

Contoh:

```text
https://nama-domain-atau-ngrok.ngrok-free.dev/zoom/callback
```

5. Tambahkan URL yang sama ke OAuth allow list.
6. Salin `Client ID` dan `Client Secret` dari Zoom.
7. Masukkan credential tersebut di aplikasi.
8. Klik hubungkan, lalu authorize akun Zoom.

Setiap akun Zoom yang ditambahkan dapat memiliki credential dan callback sendiri.

## Setup Callback Zoom per Akun

Callback disetting dari:

```text
Pengaturan -> Integrasi -> Zoom Callback per Akun
```

Pilih akun Zoom dari dropdown, lalu:

1. Salin Callback URL akun tersebut.
2. Di Zoom Marketplace akun itu, buka Event Subscriptions.
3. Masukkan Callback URL ke Event notification endpoint URL.
4. Salin Secret Token dari Zoom Marketplace.
5. Tempel Secret Token ke aplikasi.
6. Aktifkan toggle Callback.
7. Simpan.
8. Validasi/simpan endpoint di Zoom Marketplace.

Setiap akun Zoom punya URL dan Secret Token sendiri. Jangan memakai Secret Token global di `.env`.

## Pilihan Notifikasi Telegram

Notifikasi Telegram dapat dipilih per akun Zoom dari:

```text
Pengaturan -> Integrasi -> Zoom Callback per Akun
```

Pilih akun Zoom dari dropdown, lalu buka bagian `Notifikasi Telegram`. Centang hanya event yang ingin dikirim sebagai pesan Telegram, kemudian klik `Simpan Akun Ini`.

Event yang tidak dicentang tetap diproses oleh aplikasi untuk sinkronisasi data meeting, status sedang berlangsung/selesai, dan link rekaman. Pengaturan ini hanya mengatur pesan Telegram.

## Event Zoom yang Wajib Dicentang

Centang 6 event ini di Zoom Marketplace:

```text
meeting.started
meeting.ended
meeting.created
meeting.updated
meeting.deleted
recording.completed
```

Fungsi masing-masing event:

- `meeting.started`: menandai meeting sebagai sedang berlangsung.
- `meeting.ended`: menandai meeting sebagai selesai.
- `meeting.created`: menyinkronkan meeting yang dibuat langsung dari Zoom ke aplikasi.
- `meeting.updated`: memperbarui data meeting di aplikasi.
- `meeting.deleted`: menghapus meeting dari aplikasi.
- `recording.completed`: menyimpan link rekaman dan passcode rekaman jika tersedia.

Setelah Zoom berhasil memanggil endpoint, status callback di aplikasi akan berubah menjadi aktif/terverifikasi.

## Tes Callback

Di halaman pengaturan callback, klik:

```text
Tes Callback
```

Tes ini mengirim pesan internal ke Telegram yang tertaut. Tes ini tidak menggantikan validasi asli dari Zoom Marketplace. Verifikasi Zoom tetap harus datang dari request Zoom.

## Setup Telegram

Isi token bot Telegram di `.env`:

```env
TELEGRAPH_BOT_TOKEN=isi_token_bot_dari_botfather
```

Jika memakai webhook Telegram publik, atur domain jika diperlukan:

```env
TELEGRAM_WEBHOOK_DOMAIN=https://nama-domain-atau-ngrok.ngrok-free.dev
```

Lalu clear config:

```bash
php artisan config:clear
```

Di aplikasi:

1. Buka `Pengaturan -> Integrasi`.
2. Klik mulai hubungkan Telegram.
3. Buka link bot yang muncul, atau kirim manual:

```text
/link KODE
```

Notifikasi callback Zoom akan dikirim ke semua akun Telegram yang tertaut ke user tersebut.

## Alur Penggunaan Harian

1. Login ke aplikasi.
2. Hubungkan satu atau beberapa akun Zoom.
3. Hubungkan Telegram jika ingin menerima notifikasi.
4. Setup callback untuk setiap akun Zoom.
5. Buat meeting dari `/meetings`, atau langsung dari Zoom.
6. Pantau status meeting di `/meetings`.
7. Jika rekaman sudah selesai diproses oleh Zoom, tombol rekaman akan muncul otomatis.

## Halaman Meeting

Halaman `/meetings` memisahkan data menjadi:

- Sedang Berlangsung.
- Mendatang & Siap Dimulai.
- Rapat Selesai.
- Rekaman, melalui filter/tombol rekaman.

Tombol rekaman hanya muncul jika event `recording.completed` sudah diterima dan payload Zoom membawa link rekaman.

## Troubleshooting

### Zoom callback checklist tapi notifikasi tidak masuk

Periksa:

- Callback aktif di aplikasi.
- Secret Token per akun sudah benar.
- Event yang dicentang sudah lengkap 6 event.
- Event tersebut sudah dicentang di bagian Notifikasi Telegram akun Zoom terkait.
- Telegram sudah tertaut.
- Zoom benar-benar mengirim event tersebut.
- `APP_URL` adalah HTTPS publik, bukan localhost.

### Meeting dibuat langsung di Zoom tidak muncul di aplikasi

Pastikan event berikut dicentang:

```text
meeting.created
meeting.updated
meeting.deleted
```

Meeting yang dibuat sebelum webhook aktif tidak otomatis masuk, karena Zoom tidak mengirim event masa lalu.

### Rapat tidak pindah ke sedang berlangsung/selesai

Pastikan event berikut dicentang:

```text
meeting.started
meeting.ended
```

Status live/selesai mengikuti event Zoom. Jika event tidak terkirim, aplikasi hanya bisa menebak dari jadwal dan durasi.

### Tombol rekaman tidak muncul

Pastikan:

- Meeting direkam di cloud Zoom.
- Event `recording.completed` dicentang.
- Zoom sudah selesai memproses rekaman.
- Akun Zoom yang dipakai adalah akun yang callback-nya aktif.

### Unsupported SSL request di Laravel

Jika muncul log seperti:

```text
Invalid request (Unsupported SSL request)
```

Biasanya browser/Zoom/ngrok mengakses `https://127.0.0.1:8000`, padahal `php artisan serve` hanya HTTP. Gunakan:

```text
http://127.0.0.1:8000
```

atau gunakan URL HTTPS publik dari ngrok sebagai `APP_URL`.

## Perintah Berguna

```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:list
npm run build
```

## Catatan Keamanan

- Jangan commit file `.env`.
- `Client Secret`, token Zoom, dan webhook secret disimpan terenkripsi oleh aplikasi.
- Setiap akun Zoom harus memakai Secret Token webhook masing-masing.
- Gunakan HTTPS publik untuk callback production.
