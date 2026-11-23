# Panduan Menyiapkan Laravel dan Membuat API

Dokumen ini menjelaskan kebutuhan perangkat, cara memasang Laravel dari awal, cara mengaktifkan routing API, dan cara menjalankan proyek Web-DosQueue-V1.

> Panduan instalasi di bawah mengikuti dokumentasi Laravel 13. Persyaratan versi proyek saat ini adalah PHP `^8.3`, Laravel `^13.17`, Sanctum `^4.0`, Node.js yang didukung Vite 8, dan npm. Periksa kembali versi yang tercantum di `composer.json` serta `package.json` jika konfigurasi proyek berubah.

## 1. Perangkat yang Dibutuhkan

- **PHP 8.3 atau lebih baru.** Laravel 13 memerlukan PHP minimal 8.3. PHP 8.5 dapat digunakan untuk mengikuti versi yang saat ini tersedia pada lingkungan pengembangan proyek.
- **Composer 2.** Composer mengunduh dan mengelola paket PHP, termasuk Laravel dan Sanctum.
- **Node.js dan npm.** Vite 8 memerlukan Node.js `20.19+` atau `22.12+`. Gunakan versi Node.js LTS yang memenuhi syarat tersebut.
- **MariaDB atau MySQL** jika ingin menyimpan data di server basis data. SQLite dapat digunakan untuk pengembangan lokal sederhana, tetapi konfigurasi proyek ini menggunakan driver MySQL.
- **Git** untuk mengambil dan mengelola salinan kode proyek.
- Editor kode seperti Visual Studio Code.

Dokumentasi resmi Laravel 13 menyediakan [panduan instalasi](https://laravel.com/docs/13.x/installation) dan pilihan pemasangan PHP, Composer, serta Laravel Installer untuk Linux, macOS, dan Windows. Setelah instalasi, tutup dan buka kembali terminal, lalu periksa perintah yang tersedia:

```bash
php -v
composer --version
node --version
npm --version
git --version
```

Laravel juga mencantumkan kebutuhan ekstensi PHP pada [dokumentasi server requirements](https://laravel.com/docs/13.x/deployment#server-requirements). Jika Composer melaporkan ekstensi yang belum tersedia, aktifkan atau pasang ekstensi tersebut pada instalasi PHP yang sedang digunakan, lalu jalankan kembali perintah Composer.

## 2. Memasang PHP dan Composer

### Linux

Dokumentasi Laravel saat ini menyediakan skrip resmi `php.new` untuk Linux yang memasang PHP, Composer, dan Laravel Installer. Jalankan perintah berikut di terminal:

```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

Tutup dan buka kembali terminal setelah proses instalasi. Kemudian periksa versi dengan `php -v` dan `composer --version`.

### macOS

Jalankan skrip resmi untuk macOS dari terminal:

```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Tutup dan buka kembali terminal setelah instalasi selesai, lalu periksa versi PHP dan Composer.

### Windows

Buka PowerShell sebagai Administrator dan jalankan perintah instalasi yang disediakan dokumentasi resmi Laravel:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Tutup dan buka kembali PowerShell setelah instalasi. Kemudian jalankan `php -v` dan `composer --version`.

> Alternatifnya, pasang PHP dan Composer secara terpisah mengikuti panduan resmi [PHP](https://www.php.net/manual/en/install.php) dan [Composer](https://getcomposer.org/download/). Pastikan PHP yang dipakai terminal memenuhi versi Laravel dan ekstensi yang diperlukan.

## 3. Memasang Node.js dan npm

Unduh Node.js LTS dari [situs resmi Node.js](https://nodejs.org/). Pilih versi yang memenuhi syarat Vite 8 (`20.19+` atau `22.12+`). Pemasang Node.js menyertakan npm.

Setelah instalasi, tutup dan buka kembali terminal, lalu periksa:

```bash
node --version
npm --version
```

## 4. Membuat Proyek Laravel Baru

Pastikan PHP dan Composer sudah dapat dijalankan. Buat proyek baru menggunakan Composer:

```bash
composer create-project laravel/laravel:^13.0 dosqueue
cd dosqueue
```

Perintah tersebut mengunduh skeleton Laravel dan paket PHP yang dibutuhkan. Nama `dosqueue` dapat diganti dengan nama proyek lain.

Siapkan berkas konfigurasi lokal dan kunci aplikasi:

```bash
cp .env.example .env
php artisan key:generate --no-interaction
```

Di Windows PowerShell, gunakan `Copy-Item .env.example .env` sebagai pengganti `cp`.

## 5. Mengaktifkan API Laravel

Laravel 13 menyediakan perintah Artisan untuk menambahkan dukungan API. Dari direktori proyek, jalankan:

```bash
php artisan install:api --no-interaction
```

Perintah `install:api` memasang Laravel Sanctum, menyiapkan file route API `routes/api.php`, dan menambahkan migration tabel token Sanctum. Jika perintah meminta izin untuk menjalankan migration, pastikan konfigurasi database sudah benar sebelum menyetujuinya.

Route di `routes/api.php` otomatis mendapat awalan `/api`. Karena itu, route yang ditulis sebagai `/waves` akan diakses melalui `/api/waves`.

Contoh endpoint publik sederhana di `routes/api.php`:

```php
use Illuminate\Support\Facades\Route;

Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'API aktif.',
    ]);
});
```

Endpoint tersebut dapat dipanggil melalui `GET /api/status`. Untuk mencoba API, jalankan server Laravel lalu buka alamat `http://127.0.0.1:8000/api/status` di browser atau gunakan aplikasi seperti Postman.

### Melindungi endpoint dengan Sanctum

Tambahkan middleware `auth:sanctum` pada endpoint yang hanya boleh digunakan oleh pengguna yang sudah login:

```php
Route::middleware('auth:sanctum')->get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
});
```

Untuk autentikasi berbasis token, model `User` perlu menggunakan trait `Laravel\Sanctum\HasApiTokens`. Setelah pengguna berhasil login, backend dapat membuat token dengan `$user->createToken('nama-token')->plainTextToken`. Pemanggil API mengirim token pada header berikut:

```text
Authorization: Bearer TOKEN_AKSES
```

Pelajari rincian mode autentikasi dan token pada [dokumentasi Laravel Sanctum](https://laravel.com/docs/13.x/sanctum). API tidak otomatis membuat endpoint login aplikasi; endpoint login dan alur akun tetap perlu dibuat atau disediakan oleh starter kit.

## 6. Menyiapkan Database

Buat database kosong pada MariaDB atau MySQL, lalu sesuaikan variabel berikut di `.env` dengan nama database dan akun lokal:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=nama_pengguna
DB_PASSWORD=kata_sandi_lokal
```

Jangan memasukkan kata sandi asli ke Git atau dokumen publik. File `.env` berisi konfigurasi lokal dan tidak boleh dibagikan.

Setelah database tersedia, jalankan migration:

```bash
php artisan migrate --no-interaction
```

## 7. Memasang Aset Frontend dan Menjalankan Laravel Baru

Pasang paket JavaScript, lalu jalankan server frontend dan Laravel:

```bash
npm install
npm run build
php artisan serve
```

Untuk pengembangan frontend dengan pemuatan ulang otomatis, buka terminal kedua dan jalankan:

```bash
npm run dev
```

Server Laravel berjalan secara lokal, biasanya di `http://127.0.0.1:8000`.

## 8. Menyiapkan dan Menjalankan Proyek Ini

Jika kode proyek sudah tersedia di komputer, **jangan jalankan `composer create-project` atau `php artisan install:api` lagi**. Proyek ini sudah memiliki `routes/api.php`, route API yang digunakan, Sanctum, migration token, serta konfigurasi API. Perintah `install:api` ditujukan untuk menyiapkan API pada proyek Laravel baru dan dapat mengubah berkas proyek.

Masuk ke direktori proyek, lalu pasang dependensi PHP:

```bash
composer install
```

Siapkan `.env` apabila belum ada. Di Linux atau macOS:

```bash
cp .env.example .env
```

Di Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Buat application key:

```bash
php artisan key:generate --no-interaction
```

Periksa konfigurasi `DB_*` di `.env` dan pastikan database yang disebutkan sudah dibuat. Migration proyek membuat tabel gelombang, ruangan, tiket, kolom penugasan petugas, serta token akses. Untuk membuat tabel dan mengisi data contoh:

```bash
php artisan migrate --seed --no-interaction
```

Seeder membuat tiga gelombang, tiga ruangan, dan akun petugas contoh. Periksa `database/seeders/DatabaseSeeder.php` untuk rincian datanya. Jalankan seeder hanya pada database pengembangan yang memang boleh diisi data contoh.

> Jika database sudah berisi data penting, jangan gunakan `migrate:fresh`, karena perintah itu menghapus seluruh tabel sebelum membuatnya kembali.

Pasang dependensi frontend dan build aset:

```bash
npm install
npm run build
```

Jalankan Laravel:

```bash
php artisan serve
```

Untuk mengubah CSS atau JavaScript selama pengembangan, buka terminal kedua dan jalankan `npm run dev`.

## 9. Memeriksa Route API dan Mencoba Endpoint

Daftar route API dapat dilihat dengan:

```bash
php artisan route:list --path=api
```

Untuk mencoba endpoint daftar gelombang pada server lokal:

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/waves
```

Endpoint petugas yang dilindungi membutuhkan token Sanctum pada header `Authorization: Bearer ...`. Daftar endpoint aplikasi beserta alur permintaan dan responsnya dijelaskan di bagian API pada [README.md](README.md).

## Referensi Resmi

- [Laravel 13: Instalasi](https://laravel.com/docs/13.x/installation)
- [Laravel 13: Deployment requirements PHP](https://laravel.com/docs/13.x/deployment#server-requirements)
- [Laravel 13: Routing](https://laravel.com/docs/13.x/routing)
- [Laravel 13: Sanctum](https://laravel.com/docs/13.x/sanctum)
- [Composer: Unduh dan instalasi](https://getcomposer.org/download/)
- [Node.js: Unduh](https://nodejs.org/)
