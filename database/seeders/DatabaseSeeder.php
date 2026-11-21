<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\User;
use App\Models\Wave;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Master Gelombang
        $wave1 = Wave::create([
            'name' => 'Gelombang 1',
            'code' => 'G1',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'max_quota' => 200,
            'is_active' => true,
        ]);

        Wave::create([
            'name' => 'Gelombang 2',
            'code' => 'G2',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'max_quota' => 200,
            'is_active' => true,
        ]);

        Wave::create([
            'name' => 'Gelombang 3',
            'code' => 'G3',
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'max_quota' => 200,
            'is_active' => true, // Contoh gelombang non-aktif
        ]);

        // 2. Seed Master Ruangan
        $room1 = Room::create([
            'name' => 'Ruang 1',
            'code' => 'R1',
            'room_number' => 1,
            'is_active' => true,
        ]);

        $room2 = Room::create([
            'name' => 'Ruang 2',
            'code' => 'R2',
            'room_number' => 2,
            'is_active' => true,
        ]);

        $room3 = Room::create([
            'name' => 'Ruang 3',
            'code' => 'R3',
            'room_number' => 3,
            'is_active' => true,
        ]);

        // 3. Seed Akun Petugas (Sesuai contoh spesifikasi)
        $staffAccounts = [
            [
                'name' => 'Petugas A',
                'email' => 'petugas1@gmail.com',
                'room_id' => $room1->id,
            ],
            [
                'name' => 'Petugas B',
                'email' => 'petugas2@gmail.com',
                'room_id' => $room2->id,
            ],
            [
                'name' => 'Petugas C',
                'email' => 'petugas3@gmail.com',
                'room_id' => $room3->id,
            ],
        ];

        foreach ($staffAccounts as $staff) {
            User::create([
                'name' => $staff['name'],
                'email' => $staff['email'],
                'password' => Hash::make('password123'),
                'wave_id' => $wave1->id,
                'room_id' => $staff['room_id'],
            ]);
        }
    }
}