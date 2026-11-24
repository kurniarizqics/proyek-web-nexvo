# Nexvo Finance

Website untuk mengatur alur kas dan pengajuan dana di dalam organisasi nexvo studio

## 🛠️ Teknologi yang Digunakan

- **Framework:** Laravel 11
- **Bahasa Pemrograman:** PHP 8.3
- **Database:** MySQL
- **Frontend:** JS, Bootstrap
- **Package Manager:** Composer
- **Version Control:** Git dan GitHub

## 📋 Persyaratan Sistem

Sebelum menjalankan project, pastikan perangkat sudah memiliki:

- PHP 8.3 keatas.
- Composer.
- MySQL atau MariaDB.
- Git.
- Web browser.
- Visual Studio Code.

## 🚀 Instalasi Project

Ikuti langkah-langkah berikut untuk menjalankan project di komputer lokal.


### 1. Clone Repository

Buka terminal di jendela laragon dan jalankan perintah:
-pastikan directori yang terpilih adalah 'C:\laragon\www'

```bash
git clone https://github.com/kurniarizqics/proyek-web-nexvo.git
```

Masuk ke direktori project:

```bash
cd proyek-web-nexvo
```

### 2. Install Dependency Laravel

Jalankan perintah:

```bash
composer install
```

Perintah ini digunakan untuk menginstal seluruh package PHP yang dibutuhkan oleh project.

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`.

Untuk Windows:

```bash
copy .env.example .env
```

Untuk Linux/macOS:

```bash
cp .env.example .env
```

### 4. Generate Application Key

Jalankan:

```bash
php artisan key:generate
```

### 5. Konfigurasi Database

Buat database baru melalui phpMyAdmin, HeidiSQL, atau MySQL.

Kemudian buka file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexvo_finance
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan nama database, username, dan password dengan konfigurasi MySQL masing-masing.

### 6. Migrasi Database

Jalankan perintah:

```bash
php artisan migrate
```

Jika project menyediakan seeder untuk mengisi data awal, jalankan:

```bash
php artisan db:seed
```

Biarkan terminal tetap berjalan selama pengembangan.

### 8. Jalankan Website

Buka terminal baru dan jalankan:

```bash
php artisan serve
```

Buka browser dan akses alamat:

```text
http://127.0.0.1:8000
```

Website sekarang sudah dapat diakses melalui browser.

---

## 💻 Panduan Penggunaan Website

Berikut panduan umum penggunaan website:

### 1. Mengakses Website
- Buka browser.
- Masukkan alamat `http://127.0.0.1:8000`.
- Tunggu hingga halaman utama website ditampilkan.

### 2. Login
- Buka halaman login.
- Masukkan username/email dan password yang telah terdaftar.
- Klik tombol Login untuk masuk ke sistem.

### 3. Menggunakan Fitur Website
- Navigasikan menu yang tersedia pada dashboard.
- Pilih fitur sesuai kebutuhan.
- Isi formulir yang disediakan jika diperlukan.
- Klik tombol Simpan atau Submit untuk menyimpan data.

### 4. Logout
- Klik menu profil atau tombol Logout.
- Sistem akan mengakhiri sesi pengguna dan mengarahkan kembali ke halaman login.

*Catatan: Sesuaikan panduan ini dengan fitur yang benar-benar tersedia pada website.*

---

## 📁 Struktur Project

Berikut beberapa direktori utama dalam Laravel:

| Direktori/File | Fungsi |
|---|---|
| `app/` | Logika aplikasi, model, dan controller |
| `bootstrap/` | Konfigurasi awal aplikasi |
| `config/` | Konfigurasi Laravel |
| `database/` | Migration dan seeder |
| `public/` | File publik dan aset |
| `resources/` | View, CSS, dan JavaScript |
| `routes/` | Pengaturan routing |
| `storage/` | Penyimpanan file dan log |
| `.env` | Konfigurasi environment |
| `composer.json` | Dependency PHP |

## 🔄 Memperbarui Project

Untuk mengambil perubahan terbaru dari repository GitHub:

```bash
git pull origin main
```

Jika terdapat perubahan dependency PHP, jalankan kembali:

```bash
composer install
```

Jika terdapat perubahan dependency frontend, jalankan:

```bash
npm install
```

## 👥 Tim Pengembang

| Nama | Peran |
|---|---|
| Nama Anggota 1 | Project Manager |
| Nama Anggota 2 | Backend Developer |
| Nama Anggota 3 | Frontend Developer |
| Nama Anggota 4 | Database Developer |

## 📌 Catatan

- File `.env` tidak boleh diunggah ke repository GitHub.
- Jangan mengunggah informasi sensitif seperti password database dan API Key.
- Pastikan setiap anggota menggunakan konfigurasi database lokal masing-masing.
- Lakukan commit dan push secara berkala agar perubahan project dapat dikelola dengan baik.

## 📄 Lisensi

Project ini dikembangkan untuk keperluan pembelajaran dan/atau tugas kelompok.