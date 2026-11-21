<?php

namespace App\Services;

use App\Models\Room;
use App\Models\Ticket;
use RuntimeException;

class RoundRobinService
{
    /**
     * Menentukan ruangan aktif berikutnya secara Round-Robin pada gelombang tertentu.
     */
    public function getNextRoom(int $waveId): Room
    {
        // Ambil semua ruangan aktif berurutan berdasarkan nomor ruangan
        $activeRooms = Room::where('is_active', true)
            ->orderBy('room_number', 'asc')
            ->get();

        if ($activeRooms->isEmpty()) {
            throw new RuntimeException('Tidak ada ruangan pelayanan yang aktif saat ini.');
        }

        // Cari tiket terakhir yang dibuat pada gelombang ini untuk melihat ruangan terakhir yang dipakai
        $lastTicket = Ticket::where('wave_id', $waveId)
            ->orderBy('sequence_number', 'desc')
            ->first();

        // Jika belum ada tiket sama sekali di gelombang ini, mulai dari ruangan aktif pertama
        if (! $lastTicket) {
            return $activeRooms->first();
        }

        // Cari posisi index ruangan terakhir di dalam daftar ruangan aktif saat ini
        $lastRoomIndex = $activeRooms->search(fn (Room $room) => $room->id === $lastTicket->room_id);

        // Jika ruangan terakhir sudah dinonaktifkan atau berada di urutan paling akhir, kembali ke index 0
        if ($lastRoomIndex === false || ($lastRoomIndex + 1) >= $activeRooms->count()) {
            return $activeRooms->first();
        }

        // Pilih ruangan aktif berikutnya
        return $activeRooms->get($lastRoomIndex + 1);
    }
}