# Panduan Menyiapkan Laravel dan Membuat API

Dokumen ini menjelaskan kebutuhan perangkat, cara memasang Laravel dari awal, cara mengaktifkan routing API, dan cara menjalankan proyek Web-DosQueue-V1.

> Panduan instalasi di bawah mengikuti dokumentasi Laravel 13. Persyaratan versi proyek saat ini adalah PHP `^8.3`, Laravel `^13.17`, Sanctum `^4.0`, Node.js yang didukung Vite 8, dan npm. Periksa kembali versi yang tercantum di `composer.json` serta `package.json` jika konfigurasi proyek berubah.

## Daftar Isi

- [Perangkat dan instalasi](#1-perangkat-yang-dibutuhkan)
- [Membuat proyek Laravel dan menyiapkan API](#4-membuat-proyek-laravel-baru)
- [Menyiapkan dan menjalankan proyek yang sudah ada](#8-menyiapkan-dan-menjalankan-proyek-ini)
- [Tutorial membangun website antrean](#tutorial-lanjutan-membuat-website-antrean)
- [File sumber yang dapat disalin](#23-file-sumber-proyek-untuk-disalin)
- [Kendala umum](#kendala-yang-sering-terjadi)
- [Referensi resmi](#referensi-resmi)

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
composer create-project laravel/laravel nama-projek
cd dosqueue
```

Perintah tersebut mengunduh skeleton Laravel dan paket PHP yang dibutuhkan. Ganti `nama-projek` dengan nama proyek yang kamu inginkan, lalu masuk ke folder proyek dengan `cd nama-projek`.

Siapkan berkas konfigurasi lokal dan kunci aplikasi:

```bash
cp .env.example .env
php artisan key:generate --no-interaction
```

Di Windows PowerShell, gunakan `Copy-Item .env.example .env` sebagai pengganti `cp`.

## 5. Mengaktifkan API Laravel

Laravel 13 menyediakan perintah Artisan untuk menambahkan dukungan API. Dari direktori proyek, jalankan:

```bash
php artisan install:api --no-interaction --without-migration-prompt
```

Perintah `install:api` memasang Laravel Sanctum, menyiapkan file route API `routes/api.php`, dan menambahkan migration tabel token Sanctum. Opsi `--without-migration-prompt` mencegah perintah menjalankan migration sebelum database siap. Jalankan migration sendiri setelah mengatur database pada langkah berikutnya.

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

Seeder membuat tiga gelombang, tiga ruangan, dan akun petugas contoh. Periksa `database/seeders/DatabaseSeeder.php` untuk rincian datanya. Jalankan `--seed` hanya pada database pengembangan kosong atau saat memang ingin memasukkan data demo lagi; menjalankan seeder berulang dapat membuat data duplikat. Jika database sudah pernah diisi dan hanya perlu menjalankan migration baru, gunakan `php artisan migrate --no-interaction`.

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

## Tutorial Lanjutan: Membuat Website Antrean

Bagian ini melanjutkan proyek Laravel kosong sampai tersedia alur dasar website antrean: pengunjung memilih gelombang, menerima nomor tiket, melihat posisi antrean, dan petugas memproses tiket. Contoh mengikuti struktur dan versi Web-DosQueue-V1.

### Cara mengikuti contoh

1. Jalankan perintah dari direktori proyek Laravel.
2. Jika perintah `make:` menghasilkan nama file dengan timestamp berbeda, edit file yang baru dibuat itu.
3. Salin kode ke file yang disebutkan sebelum kode tersebut.
4. Setelah membuat migration atau mengubah model, jalankan migration dan periksa route API.
5. Jika sudah memiliki Web-DosQueue-V1, file lengkapnya telah ada di jalur proyek yang disebutkan. Jangan mengganti seluruh file yang telah dimodifikasi tim tanpa meninjau perbedaannya.

Contoh di bawah adalah versi pembelajaran yang merangkum alur utama. Daftar file sumber di tiap bagian menunjukkan implementasi lengkap proyek, termasuk respons error, status HTTP, validasi, dan tampilan yang digunakan.

### 10. Rancang Data dan Status Tiket

Alur antrean menggunakan empat status:

| Status | Arti |
|---|---|
| `WAITING` | Tiket sudah dibuat dan sedang menunggu. |
| `CALLED` | Petugas sudah memanggil tiket dan sedang melayani pengunjung. |
| `COMPLETED` | Pelayanan tiket sudah selesai. |
| `DELETED` | Tiket selesai disembunyikan dari riwayat. |

Basis data mempunyai tabel `waves` (gelombang), `rooms` (ruangan), `tickets` (tiket), `users` (akun petugas), dan `personal_access_tokens` (token Sanctum). Relasi utamanya: satu gelombang memiliki banyak tiket, satu ruangan melayani banyak tiket, dan satu petugas ditugaskan ke ruangan.

### 11. Buat Migration Tabel

Buat file migration dengan Artisan. Tabel `waves` dan `rooms` harus dibuat sebelum `tickets`, karena tiket menyimpan foreign key ke keduanya.

```bash
php artisan make:migration create_waves_table --no-interaction
php artisan make:migration create_rooms_table --no-interaction
php artisan make:migration create_tickets_table --no-interaction
php artisan make:migration add_wave_and_room_to_users_table --no-interaction
```

Pada file `create_waves_table`, isi method `up()` dan `down()` seperti ini:

```php
public function up(): void
{
    Schema::create('waves', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code', 10)->unique();
        $table->time('start_time');
        $table->time('end_time');
        $table->unsignedBigInteger('max_quota');
        $table->boolean('is_active')->default(true)->index();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('waves');
}
```

Tambahkan import di bagian atas file migration jika belum ada:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
```

Pada migration `rooms`, simpan nama dan kode ruangan serta nomor urut Round-Robin:

```php
public function up(): void
{
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code', 10)->unique();
        $table->unsignedInteger('room_number')->unique();
        $table->boolean('is_active')->default(true)->index();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('rooms');
}
```

Pada migration `tickets`, hubungkan tiket ke gelombang dan ruangan. Constraint unik mencegah nomor tiket dan nomor urut gelombang menjadi duplikat:

```php
public function up(): void
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('token', 64)->unique();
        $table->string('ticket_number', 30)->unique();
        $table->foreignId('wave_id')->constrained('waves')->restrictOnDelete();
        $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
        $table->unsignedInteger('sequence_number');
        $table->string('status', 20)->default('WAITING')->index();
        $table->timestamp('called_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();

        $table->unique(['wave_id', 'sequence_number']);
        $table->index(['wave_id', 'room_id', 'status', 'sequence_number']);
    });
}

public function down(): void
{
    Schema::dropIfExists('tickets');
}
```

Migration `add_wave_and_room_to_users_table` menambahkan penugasan petugas ke tabel `users` bawaan Laravel:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('wave_id')->nullable()->constrained('waves')->nullOnDelete();
        $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropConstrainedForeignId('wave_id');
        $table->dropConstrainedForeignId('room_id');
    });
}
```

`php artisan install:api` membuat migration `personal_access_tokens` untuk Sanctum. Jangan buat tabel itu sekali lagi jika migration sudah dibuat oleh perintah tersebut.

Jalankan migration:

```bash
php artisan migrate --no-interaction
```

Jika migration gagal karena tabel atau constraint sudah pernah dibuat, baca pesan error dan periksa status migration dengan `php artisan migrate:status`. Untuk database yang berisi data penting, jangan menghapus tabel sebagai cara memperbaiki migration.

### 12. Buat Enum dan Model

Buat enum status dan model-model aplikasi:

```bash
php artisan make:model Wave --no-interaction
php artisan make:model Room --no-interaction
php artisan make:model Ticket --no-interaction
php artisan make:enum TicketStatus --string --no-interaction
```

Pada `app/Enums/TicketStatus.php`, definisikan status sebagai PHP backed enum:

```php
<?php

namespace App\Enums;

enum TicketStatus: string
{
    case WAITING = 'WAITING';
    case CALLED = 'CALLED';
    case COMPLETED = 'COMPLETED';
    case DELETED = 'DELETED';

    /** @return list<string> */
    public static function activeStatuses(): array
    {
        return [self::WAITING->value, self::CALLED->value];
    }
}
```

Pada model, gunakan `$fillable` agar hanya kolom yang memang boleh diisi oleh proses aplikasi yang dapat dimass-assign. Tambahkan relasi dan cast enum/datetime pada `app/Models/Ticket.php`:

```php
<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'token', 'ticket_number', 'wave_id', 'room_id',
        'sequence_number', 'status', 'called_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'status' => TicketStatus::class,
            'called_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [TicketStatus::WAITING, TicketStatus::CALLED], true);
    }
}
```

Pada model `Wave` tambahkan kolom yang boleh diisi dan relasi tiket:

```php
protected $fillable = ['name', 'code', 'start_time', 'end_time', 'max_quota', 'is_active'];

protected function casts(): array
{
    return ['max_quota' => 'integer', 'is_active' => 'boolean'];
}

public function tickets(): HasMany
{
    return $this->hasMany(Ticket::class);
}
```

Pada model `Room`, gunakan `fillable` untuk `name`, `code`, `room_number`, dan `is_active`, lalu tambahkan relasi `tickets(): HasMany` yang mengembalikan `$this->hasMany(Ticket::class)`. Tambahkan import `HasMany` dan `Ticket` di bagian atas kedua model.

Model `User` perlu token Sanctum dan relasi ruangan. Buka `app/Models/User.php` yang telah dibuat Laravel, lalu tambahkan:

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

// Di dalam class User:
use HasApiTokens;

protected $fillable = ['name', 'email', 'password', 'wave_id', 'room_id'];

protected $hidden = ['password', 'remember_token'];

protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}

public function room(): BelongsTo
{
    return $this->belongsTo(Room::class);
}
```

Jangan membuat ulang class `User`; gabungkan trait, kolom, dan relasi ke file bawaan. Implementasi lengkap relasi proyek ada pada `app/Models/Wave.php`, `Room.php`, `Ticket.php`, dan `User.php`.

### 13. Buat Pembagian Ruangan Round-Robin

Round-Robin memberikan tiket ke ruangan aktif secara bergantian menurut `room_number`. Buat `app/Services/RoundRobinService.php` (folder dapat dibuat jika belum ada):

```php
<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Ticket;
use RuntimeException;

class RoundRobinService
{
    public function getNextRoom(int $waveId): Room
    {
        $activeRooms = Room::where('is_active', true)
            ->orderBy('room_number')
            ->get();

        if ($activeRooms->isEmpty()) {
            throw new RuntimeException('Tidak ada ruangan aktif.');
        }

        $lastTicket = Ticket::where('wave_id', $waveId)
            ->orderByDesc('sequence_number')
            ->first();

        if (! $lastTicket) {
            return $activeRooms->first();
        }

        $lastRoomIndex = $activeRooms->search(
            fn (Room $room): bool => (int) $room->id === (int) $lastTicket->room_id
        );

        if ($lastRoomIndex === false || $lastRoomIndex + 1 >= $activeRooms->count()) {
            return $activeRooms->first();
        }

        return $activeRooms->get($lastRoomIndex + 1);
    }
}
```

Misalnya tersedia tiga ruangan: tiket pertama menuju R1, tiket kedua R2, tiket ketiga R3, lalu pembagian kembali ke R1. Jika sebuah ruangan dinonaktifkan, daftar ruangan aktif dipakai untuk giliran berikutnya.

### 14. Validasi Permintaan Pengambilan Tiket

Buat Form Request:

```bash
php artisan make:request StoreTicketRequest --no-interaction
```

Isi `app/Http/Requests/StoreTicketRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wave_id' => ['required', 'integer', 'exists:waves,id'],
            'current_token' => ['nullable', 'string', 'max:64'],
        ];
    }
}
```

Form Request memeriksa bentuk data. Ia tidak menggantikan pengecekan kuota, status aktif gelombang, atau aturan bisnis yang harus diperiksa saat penyimpanan.

### 15. Buat Service untuk Tiket dan Kuota

Buat `app/Services/TicketService.php`. Service mengunci baris gelombang dalam transaksi saat menghitung kuota dan menyimpan tiket. Dengan demikian, dua permintaan bersamaan tidak sama-sama melewati pengecekan kuota berdasarkan nilai lama.

```php
<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\Wave;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketService
{
    public function __construct(private RoundRobinService $roundRobinService) {}

    /** @return array{ticket: Ticket, is_existing: bool} */
    public function createTicket(int $waveId, ?string $existingToken = null): array
    {
        if ($existingToken) {
            $activeTicket = $this->getActiveTicketByToken($existingToken);

            if ($activeTicket) {
                return ['ticket' => $activeTicket, 'is_existing' => true];
            }
        }

        return DB::transaction(function () use ($waveId): array {
            $wave = Wave::whereKey($waveId)->lockForUpdate()->first();

            if (! $wave || ! $wave->is_active) {
                throw new RuntimeException('Gelombang tidak ditemukan atau tidak aktif.');
            }

            $usedQuota = Ticket::where('wave_id', $wave->id)->count();

            if ($usedQuota >= $wave->max_quota) {
                throw new RuntimeException('Kuota gelombang sudah habis.');
            }

            $nextSequence = (int) Ticket::where('wave_id', $wave->id)
                ->max('sequence_number') + 1;
            $room = $this->roundRobinService->getNextRoom($wave->id);
            $number = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);

            $ticket = Ticket::create([
                'token' => 'tkt_'.bin2hex(random_bytes(16)),
                'ticket_number' => "{$wave->code}-{$room->code}-{$number}",
                'wave_id' => $wave->id,
                'room_id' => $room->id,
                'sequence_number' => $nextSequence,
                'status' => TicketStatus::WAITING,
            ]);

            return [
                'ticket' => $ticket->load(['wave', 'room']),
                'is_existing' => false,
            ];
        });
    }

    public function getActiveTicketByToken(?string $token): ?Ticket
    {
        if (! $token) {
            return null;
        }

        return Ticket::with(['wave', 'room'])
            ->where('token', $token)
            ->whereIn('status', TicketStatus::activeStatuses())
            ->first();
    }
}
```

Pada implementasi proyek, service ini juga memiliki method untuk mencari tiket, menghitung jumlah antrean di depan tiket, dan membentuk data monitor. Salin keseluruhan implementasi dari `app/Services/TicketService.php` jika ingin sama persis. Constraint database `unique(['wave_id', 'sequence_number'])` menjadi perlindungan tambahan untuk nomor urut.

### 16. Buat Bentuk Respons JSON dan Controller Pengunjung

Buat resource dan controller:

```bash
php artisan make:resource TicketResource --no-interaction
php artisan make:resource WaveResource --no-interaction
php artisan make:controller Api/Guest/WaveController --no-interaction
php artisan make:controller Api/Guest/TicketController --no-interaction
```

Contoh `TicketResource` menyaring kolom yang boleh dikirim ke frontend:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'token' => $this->token,
            'sequence_number' => $this->sequence_number,
            'status' => $this->status->value,
            'wave' => $this->whenLoaded('wave', fn () => [
                'id' => $this->wave->id,
                'name' => $this->wave->name,
                'code' => $this->wave->code,
            ]),
            'room' => $this->whenLoaded('room', fn () => [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ]),
            'called_at' => $this->called_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
```

Di `app/Http/Controllers/Api/Guest/WaveController.php`, endpoint daftar gelombang menggunakan `withCount()` agar hanya menghitung tiket, bukan memuat semua tiket:

```php
<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\WaveResource;
use App\Models\Wave;
use Illuminate\Http\JsonResponse;

class WaveController extends Controller
{
    public function index(): JsonResponse
    {
        $waves = Wave::withCount('tickets')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar gelombang berhasil diambil.',
            'data' => [
                'can_take_ticket' => $waves->contains(
                    fn (Wave $wave): bool => $wave->is_active
                        && $wave->tickets_count < $wave->max_quota
                ),
                'waves' => WaveResource::collection($waves),
            ],
        ]);
    }
}
```

Di `app/Http/Resources/WaveResource.php`, kembalikan kuota dalam format yang mudah ditampilkan:

```php
public function toArray(Request $request): array
{
    $used = (int) ($this->tickets_count ?? 0);
    $remaining = max(0, $this->max_quota - $used);

    return [
        'id' => $this->id,
        'name' => $this->name,
        'code' => $this->code,
        'max_quota' => $this->max_quota,
        'used_quota' => $used,
        'remaining_quota' => $remaining,
        'is_active' => $this->is_active,
        'is_available' => $this->is_active && $remaining > 0,
    ];
}
```

Pada controller tiket, gunakan Form Request dan service. Contoh method `store()` berikut mengembalikan `201` saat membuat tiket baru dan `200` saat mengembalikan tiket aktif yang sudah dimiliki pengunjung:

```php
public function store(StoreTicketRequest $request, TicketService $ticketService): JsonResponse
{
    try {
        $result = $ticketService->createTicket(
            waveId: (int) $request->validated('wave_id'),
            existingToken: $request->header('X-Ticket-Token')
                ?? $request->validated('current_token'),
        );

        return response()->json([
            'success' => true,
            'message' => $result['is_existing']
                ? 'Tiket aktif sebelumnya ditemukan.'
                : 'Tiket berhasil dibuat.',
            'data' => new TicketResource($result['ticket']),
        ], $result['is_existing'] ? 200 : 201);
    } catch (RuntimeException $exception) {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'data' => null,
        ], 422);
    }
}
```

Tambahkan import `StoreTicketRequest`, `TicketResource`, `TicketService`, `JsonResponse`, dan `RuntimeException` di controller. Untuk endpoint detail, tiket aktif, dan monitor, lihat method `active()`, `show()`, dan `monitor()` di `app/Http/Controllers/Api/Guest/TicketController.php` proyek ini.

### 17. Daftarkan Endpoint API

Tulis endpoint di `routes/api.php`. Prefix `/api` ditambahkan Laravel secara otomatis, sehingga di dalam file tulis `/waves`, bukan `/api/waves`.

```php
<?php

use App\Http\Controllers\Api\Guest\TicketController;
use App\Http\Controllers\Api\Guest\WaveController;
use App\Http\Controllers\Api\Staff\AuthController;
use App\Http\Controllers\Api\Staff\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/waves', [WaveController::class, 'index']);
Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware('throttle:ticket-creation');
Route::get('/tickets/active', [TicketController::class, 'active']);
Route::get('/tickets/{token}', [TicketController::class, 'show']);
Route::get('/tickets/{token}/monitor', [TicketController::class, 'monitor'])
    ->middleware('throttle:queue-polling');

Route::prefix('staff')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:staff-login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/queues', [QueueController::class, 'index']);
        Route::post('/tickets/{ticket}/call', [QueueController::class, 'call']);
        Route::post('/tickets/{ticket}/complete', [QueueController::class, 'complete']);
        Route::get('/history', [QueueController::class, 'history']);
        Route::delete('/tickets/{ticket}', [QueueController::class, 'destroy']);
    });
});
```

Rate limiter bernama `ticket-creation`, `staff-login`, dan `queue-polling` harus didaftarkan di `AppServiceProvider::boot()`. Contoh definisinya:

```php
RateLimiter::for('ticket-creation', fn (Request $request) =>
    Limit::perMinute(10)->by($request->ip())
);
```

Import `Illuminate\Cache\RateLimiting\Limit`, `Illuminate\Http\Request`, dan `Illuminate\Support\Facades\RateLimiter`. Implementasi proyek juga membatasi login hingga 10 permintaan per menit dan monitor hingga 30 permintaan per menit per IP.

### 18. Login Petugas dengan Sanctum

Tambahkan `HasApiTokens` ke model `User` seperti pada langkah 12. Endpoint login memvalidasi email dan kata sandi, memastikan petugas mendapat penugasan ruangan, lalu menerbitkan token.

Validasi data login dengan Form Request:

```bash
php artisan make:request StaffLoginRequest --no-interaction
```

Isi `app/Http/Requests/StaffLoginRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

Method login pada `AuthController` dapat memeriksa akun dan menerbitkan token seperti berikut:

```php
public function login(StaffLoginRequest $request): JsonResponse
{
    $user = User::with('room')
        ->where('email', $request->validated('email'))
        ->first();

    if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Email atau password salah.',
            'data' => null,
        ], 401);
    }

    if (! $user->room_id) {
        return response()->json([
            'success' => false,
            'message' => 'Akun petugas belum memiliki ruangan.',
            'data' => null,
        ], 403);
    }

    $token = $user->createToken('staff-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login berhasil.',
        'data' => [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'staff' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'room' => $user->room->only(['id', 'name', 'code']),
            ],
        ],
    ]);
}
```

Tambahkan import `StaffLoginRequest`, `User`, `Hash`, dan `JsonResponse` pada controller.

Frontend harus menyertakan hasil `access_token` pada permintaan yang memerlukan login:

```http
Authorization: Bearer TOKEN_AKSES
```

Contoh helper JavaScript untuk menyimpan token dan mengambil antrean terproteksi:

```javascript
async function staffLogin(email, password) {
    const response = await fetch('/api/staff/login', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message ?? 'Login gagal.');
    }

    localStorage.setItem('staff_access_token', result.data.access_token);
    return result.data.staff;
}

async function loadStaffQueues() {
    const token = localStorage.getItem('staff_access_token');
    const response = await fetch('/api/staff/queues', {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message ?? 'Antrean gagal dimuat.');
    }

    return result.data;
}

async function updateStaffTicket(ticketId, action) {
    if (!['call', 'complete'].includes(action)) {
        throw new Error('Tindakan tiket tidak dikenal.');
    }

    const token = localStorage.getItem('staff_access_token');
    const response = await fetch(`/api/staff/tickets/${ticketId}/${action}`, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
        throw new Error(result.message ?? 'Tiket gagal diperbarui.');
    }

    return result.data;
}
```

Saat logout, kirim `POST /api/staff/logout` dengan header token, lalu hapus token lokal menggunakan `localStorage.removeItem('staff_access_token')`.

Contoh ini mengikuti bearer token yang digunakan proyek. Token yang disimpan di `localStorage` dapat dibaca JavaScript pada halaman, jadi aplikasi produksi harus mencegah XSS dengan ketat dan mempertimbangkan autentikasi cookie/session Sanctum untuk frontend first-party yang berjalan pada domain aplikasi sendiri.

Endpoint `/api/staff/logout` menghapus token yang sedang dipakai melalui `$request->user()->currentAccessToken()->delete()`. Endpoint petugas juga harus memeriksa otorisasi tiket: autentikasi membuktikan siapa petugasnya, sedangkan policy memeriksa apakah tiket berada di ruangan tugasnya. Proyek menerapkan pemeriksaan tersebut di `app/Policies/TicketPolicy.php` dan memanggil `Gate::authorize('manage', $ticket)` sebelum mengubah tiket.

Buat policy tersebut dengan Artisan:

```bash
php artisan make:policy TicketPolicy --model=Ticket --no-interaction
```

Isi method `manage` agar petugas hanya dapat mengelola tiket di ruangannya:

```php
public function manage(User $user, Ticket $ticket): bool
{
    return $user->room_id !== null
        && (int) $user->room_id === (int) $ticket->room_id;
}
```

Pastikan file mengimpor `App\Models\Ticket` dan `App\Models\User`. Laravel menemukan policy berdasarkan nama model dan lokasi konvensional `app/Policies/`.

### 19. Proses Antrean Petugas: FIFO dan Perubahan Status

Saat memanggil tiket, backend harus memastikan tiket berstatus `WAITING`, tidak ada tiket `CALLED` lain yang masih dilayani di ruangan itu, dan tidak ada tiket menunggu yang lebih awal. Setelah itu, ubah status menjadi `CALLED` dan isi waktu `called_at`.

Perubahan status sebaiknya berlangsung dalam transaksi dengan row lock:

```php
$ticket = DB::transaction(function () use ($ticket): Ticket {
    $lockedTicket = Ticket::whereKey($ticket->id)
        ->lockForUpdate()
        ->firstOrFail();

    if ($lockedTicket->status !== TicketStatus::WAITING) {
        throw new RuntimeException('Hanya tiket WAITING yang dapat dipanggil.');
    }

    $lockedTicket->update([
        'status' => TicketStatus::CALLED,
        'called_at' => now(),
    ]);

    return $lockedTicket->fresh(['wave', 'room']);
});
```

Untuk menyelesaikan layanan, terapkan aturan serupa: hanya tiket `CALLED` yang boleh berubah ke `COMPLETED`, lalu isi `completed_at`. Kode proyek lengkap yang juga menegakkan FIFO dan satu pemanggilan aktif per ruangan ada di `app/Services/QueueService.php`.

### 20. Hubungkan Halaman dengan API

Halaman Blade proyek berada di `resources/views/guest/` dan `resources/views/staff/`. Panggilan API dilakukan dengan JavaScript `fetch`. Contoh mengambil gelombang:

```javascript
async function loadWaves() {
    const response = await fetch('/api/waves', {
        headers: { Accept: 'application/json' },
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message ?? 'Gelombang gagal dimuat.');
    }

    return result.data.waves;
}

async function renderWaves() {
    const container = document.querySelector('#waves');
    const message = document.querySelector('#message');

    try {
        const waves = await loadWaves();
        container.replaceChildren();

        for (const wave of waves) {
            const button = document.createElement('button');
            button.type = 'button';
            button.disabled = !wave.is_available;
            button.className = 'border border-neutral-900 p-4 text-left disabled:opacity-50';
            button.textContent = `${wave.name} (${wave.code}) — sisa ${wave.remaining_quota}`;
            button.addEventListener('click', () => createTicket(wave.id));
            container.append(button);
        }
    } catch (error) {
        message.textContent = error.message;
    }
}

document.addEventListener('DOMContentLoaded', renderWaves);
```

Contoh mengirim permintaan pembuatan tiket:

```javascript
async function createTicket(waveId, savedToken = null) {
    const response = await fetch('/api/tickets', {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(savedToken ? { 'X-Ticket-Token': savedToken } : {}),
        },
        body: JSON.stringify({ wave_id: waveId }),
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message ?? 'Tiket gagal dibuat.');
    }

    localStorage.setItem('antiran_ticket_token', result.data.token);
    window.location.href = `/tiket/${result.data.token}`;
}
```

Halaman `resources/views/guest/ticket.blade.php` memasukkan token dari URL ke JavaScript dan mengambil `/api/tickets/{token}/monitor` setiap 4 detik. Terapkan interval dengan `setInterval`, tangani kegagalan jaringan di `try/catch`, dan hentikan polling jika API menyatakan tiket sudah tidak tersedia.

Untuk membuat halaman monitor yang mengambil data berkala:

```javascript
async function refreshTicket(token) {
    const response = await fetch(`/api/tickets/${encodeURIComponent(token)}/monitor`, {
        headers: { Accept: 'application/json' },
    });
    const result = await response.json();

    if (!response.ok || !result.success) {
        if (response.status === 404) {
            clearInterval(pollingTimer);
        }

        throw new Error(result.message ?? 'Status tiket gagal dimuat.');
    }

    document.querySelector('#ticket-number').textContent = result.data.ticket_number;
    document.querySelector('#ticket-status').textContent = result.data.status;
    document.querySelector('#queues-ahead').textContent = result.data.queues_ahead;

    if (!result.data.is_active) {
        clearInterval(pollingTimer);
    }
}

const token = document.querySelector('#ticket-app').dataset.token;
let pollingTimer;
refreshTicket(token);
pollingTimer = setInterval(() => refreshTicket(token).catch(console.error), 4000);
```

Elemen `#ticket-app` harus memiliki `data-token="{{ $token }}"`. Elemen HTML untuk nomor tiket, status, dan jumlah antrean perlu memiliki ID yang sesuai dengan selector di JavaScript.

### Pasang Tailwind CSS v4 pada proyek Laravel kosong

Jika proyek belum memiliki Tailwind, pasang plugin Vite dan Tailwind:

```bash
npm install --save-dev tailwindcss @tailwindcss/vite
```

Pada `vite.config.js`, daftarkan plugin Tailwind bersama plugin Laravel:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

Isi `resources/css/app.css` dengan import Tailwind v4:

```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';
```

Vite memindai file Blade dan JavaScript untuk menemukan class yang digunakan, lalu membangun CSS yang dibutuhkan.

Contoh minimal tampilan Blade dengan Tailwind:

```blade
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ambil Tiket Antrean</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-100 p-6 text-neutral-950">
    <main class="mx-auto flex max-w-xl flex-col gap-6">
        <h1 class="text-3xl font-bold">Ambil Tiket Antrean</h1>
        <p id="message" role="status">Memuat gelombang...</p>
        <div id="waves" class="flex flex-col gap-3"></div>
    </main>
</body>
</html>
```

Pastikan `resources/css/app.css` mengimpor Tailwind v4 dengan `@import 'tailwindcss';`, dan `vite.config.js` menggunakan plugin `@tailwindcss/vite`. Proyek ini sudah memiliki konfigurasi tersebut. Jangan menambahkan CDN Tailwind jika aset proyek sudah dibangun lewat Vite.

Di `routes/web.php`, tampilkan halaman menggunakan route bernama:

```php
Route::get('/', fn () => view('guest.index'))->name('guest.index');
Route::get('/tiket/{token}', fn (string $token) =>
    view('guest.ticket', ['token' => $token])
)->name('guest.ticket');
```

### 21. Isi Data Contoh dengan Seeder

Buat seeder jika belum tersedia:

```bash
php artisan make:seeder DatabaseSeeder --no-interaction
```

Seeder membuat data awal gelombang, ruangan, dan akun petugas. Buat hash password dengan `Hash::make()`, jangan menyimpan kata sandi polos ke tabel:

```php
use App\Models\Room;
use App\Models\User;
use App\Models\Wave;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

public function run(): void
{
    $wave = Wave::create([
        'name' => 'Gelombang 1',
        'code' => 'G1',
        'start_time' => '08:00:00',
        'end_time' => '10:00:00',
        'max_quota' => 200,
        'is_active' => true,
    ]);

    $room = Room::create([
        'name' => 'Ruang 1',
        'code' => 'R1',
        'room_number' => 1,
        'is_active' => true,
    ]);

    User::create([
        'name' => 'Petugas Demo',
        'email' => 'petugas@example.test',
        'password' => Hash::make('ganti-password-ini'),
        'wave_id' => $wave->id,
        'room_id' => $room->id,
    ]);
}
```

Ganti email dan kata sandi demo untuk kebutuhan lokal. Jangan memakai akun atau password contoh ini pada server publik. Data seed proyek yang sebenarnya ada di `database/seeders/DatabaseSeeder.php`.

Jika memakai seeder contoh di atas, login lokal menggunakan `petugas@example.test` dan password `ganti-password-ini`. Jika menggunakan seeder yang sudah ada dalam Web-DosQueue-V1, akun demo yang dibuat antara lain `petugas1@gmail.com` dengan password lokal `password123`. Kredensial demo ini hanya untuk database pengembangan.

### 22. Jalankan dan Periksa Alur

Bangun aset, jalankan server, dan lihat route API:

```bash
npm install
npm run build
php artisan route:list --path=api
php artisan serve
```

Buka `http://127.0.0.1:8000`, lalu coba alur berikut:

1. Halaman pengunjung meminta daftar gelombang melalui `GET /api/waves`.
2. Pilih gelombang. Browser mengirim `POST /api/tickets` dengan JSON `{"wave_id": 1}`.
3. Backend menyimpan tiket `WAITING`, memberikan nomor seperti `G1-R1-001` dan token unik, lalu browser membuka halaman tiket.
4. Halaman tiket meminta `GET /api/tickets/{token}/monitor` secara berkala.
5. Login pada `/staff/login` menggunakan akun yang dibuat seeder, lalu halaman petugas meminta `GET /api/staff/queues` dengan Bearer token.
6. Petugas memanggil tiket (`WAITING` menjadi `CALLED`) dan menyelesaikan pelayanan (`CALLED` menjadi `COMPLETED`).
7. Periksa riwayat melalui `GET /api/staff/history`.

Uji endpoint publik dari terminal:

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/waves
```

Untuk membuat tiket:

```bash
curl -X POST http://127.0.0.1:8000/api/tickets \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"wave_id":1}'
```

Respons tiket menyertakan token. Pakai token tersebut menggantikan `{token}` pada endpoint detail atau monitor. Untuk endpoint petugas, login terlebih dahulu untuk memperoleh `access_token`, kemudian kirim sebagai `Authorization: Bearer access_token`.

### 23. File Sumber Proyek untuk Disalin

Jika ingin menyalin implementasi aplikasi ini secara lengkap, gunakan file berikut sebagai referensi. Struktur file dapat berbeda sedikit pada proyek baru, tetapi lokasi logikanya sama:

| Fitur | File sumber |
|---|---|
| Daftar endpoint API | `routes/api.php` |
| Halaman dan route web | `routes/web.php` |
| Status tiket | `app/Enums/TicketStatus.php` |
| Relasi tabel | `app/Models/Wave.php`, `Room.php`, `Ticket.php`, `User.php` |
| Pembagian ruangan | `app/Services/RoundRobinService.php` |
| Pembuatan tiket, kuota, token, dan data monitor | `app/Services/TicketService.php` |
| Aturan FIFO dan perubahan status | `app/Services/QueueService.php` |
| Login dan token Sanctum | `app/Http/Controllers/Api/Staff/AuthController.php` |
| Endpoint pengunjung | `app/Http/Controllers/Api/Guest/TicketController.php`, `WaveController.php` |
| Endpoint petugas | `app/Http/Controllers/Api/Staff/QueueController.php` |
| Validasi dan bentuk respons | `app/Http/Requests/`, `app/Http/Resources/` |
| Hak akses petugas | `app/Policies/TicketPolicy.php` |
| Halaman antarmuka | `resources/views/guest/`, `resources/views/staff/` |
| Data demo | `database/seeders/DatabaseSeeder.php` |

### Kendala yang Sering Terjadi

| Gejala | Yang perlu diperiksa |
|---|---|
| `php` atau `composer` tidak ditemukan | Buka ulang terminal dan pastikan lokasi executable sudah masuk `PATH`. |
| Composer mengeluhkan ekstensi PHP | Pasang/aktifkan ekstensi yang disebutkan, lalu cocokkan `php --ini` dan `php -m` dengan PHP yang digunakan terminal. |
| `could not find driver` | Aktifkan ekstensi `pdo_mysql` untuk MariaDB/MySQL atau `pdo_sqlite` untuk SQLite. |
| `Connection refused` atau database tidak ditemukan | Pastikan layanan database berjalan, database sudah dibuat, dan nilai `DB_*` pada `.env` benar. |
| `Table ... doesn't exist` | Jalankan `php artisan migrate --no-interaction` pada database yang benar. |
| `Vite manifest not found` | Jalankan `npm install` lalu `npm run build`, atau hidupkan `npm run dev` pada terminal lain. |
| `Unauthenticated` pada endpoint staff | Login dan kirim token sebagai header `Authorization: Bearer ...`. |
| `403` saat mengelola tiket | Pastikan akun petugas mempunyai `room_id` yang sama dengan ruangan tiket. |
| `422` saat membuat tiket | Periksa `wave_id`, status gelombang, kuota, dan apakah masih ada ruangan aktif. |

Kode status HTTP dan pesan JSON membantu membedakan data tidak valid (`422`), belum login (`401`), tidak memiliki izin (`403`), data tidak ditemukan (`404`), dan terlalu banyak permintaan (`429`).

## Referensi Resmi

- [Laravel 13: Instalasi](https://laravel.com/docs/13.x/installation)
- [Laravel 13: Deployment requirements PHP](https://laravel.com/docs/13.x/deployment#server-requirements)
- [Laravel 13: Routing](https://laravel.com/docs/13.x/routing)
- [Laravel 13: Sanctum](https://laravel.com/docs/13.x/sanctum)
- [Laravel 13: Migration](https://laravel.com/docs/13.x/migrations)
- [Laravel 13: Eloquent relationships](https://laravel.com/docs/13.x/eloquent-relationships)
- [Laravel 13: Validation](https://laravel.com/docs/13.x/validation)
- [Laravel 13: Authorization](https://laravel.com/docs/13.x/authorization)
- [Tailwind CSS: Laravel dan Vite](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite)
- [Composer: Unduh dan instalasi](https://getcomposer.org/download/)
- [Node.js: Unduh](https://nodejs.org/)
