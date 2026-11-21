<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QueueService
{
    /**
     * Mengambil daftar antrian aktif (CALLED & WAITING) milik petugas sesuai FIFO.
     */
    public function getActiveQueues(User $staff): Collection
    {
        return Ticket::with(['wave', 'room'])
            ->where('wave_id', $staff->wave_id)
            ->where('room_id', $staff->room_id)
            ->whereIn('status', TicketStatus::activeStatuses())
            // Urutkan CALLED di paling atas, lalu urutkan berdasarkan sequence_number terkecil (FIFO)
            ->orderByRaw("CASE WHEN status = 'CALLED' THEN 0 ELSE 1 END ASC")
            ->orderBy('sequence_number', 'asc')
            ->get();
    }

    /**
     * Memanggil tiket berikutnya (WAITING -> CALLED).
     */
    public function callTicket(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket) {
            // Kunci baris tiket yang hendak dipanggil
            $lockedTicket = Ticket::with(['wave', 'room'])
                ->where('id', $ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTicket->status !== TicketStatus::WAITING) {
                throw new RuntimeException('Hanya tiket berstatus WAITING yang dapat dipanggil.');
            }

            // Aturan V1: Wajib selesaikan tiket CALLED saat ini sebelum memanggil tiket baru
            $hasOngoingCall = Ticket::where('wave_id', $lockedTicket->wave_id)
                ->where('room_id', $lockedTicket->room_id)
                ->where('status', TicketStatus::CALLED)
                ->lockForUpdate()
                ->exists();

            if ($hasOngoingCall) {
                throw new RuntimeException('Selesaikan pelayanan tiket yang sedang dipanggil (CALLED) terlebih dahulu.');
            }

            // Aturan FIFO: Pastikan tidak ada tiket WAITING lain dengan nomor urut lebih awal di ruangan ini
            $earlierWaitingExists = Ticket::where('wave_id', $lockedTicket->wave_id)
                ->where('room_id', $lockedTicket->room_id)
                ->where('status', TicketStatus::WAITING)
                ->where('sequence_number', '<', $lockedTicket->sequence_number)
                ->exists();

            if ($earlierWaitingExists) {
                throw new RuntimeException('Harap panggil tiket sesuai urutan antrian paling awal (FIFO).');
            }

            $lockedTicket->update([
                'status' => TicketStatus::CALLED,
                'called_at' => now(),
            ]);

            return $lockedTicket->fresh(['wave', 'room']);
        });
    }

    /**
     * Menyelesaikan pelayanan tiket (CALLED -> COMPLETED).
     */
    public function completeTicket(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket) {
            $lockedTicket = Ticket::with(['wave', 'room'])
                ->where('id', $ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTicket->status !== TicketStatus::CALLED) {
                throw new RuntimeException('Hanya tiket yang sedang dipanggil (CALLED) yang dapat diselesaikan.');
            }

            $lockedTicket->update([
                'status' => TicketStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            return $lockedTicket->fresh(['wave', 'room']);
        });
    }

    /**
     * Mengambil riwayat tiket yang sudah selesai (COMPLETED) milik petugas.
     */
    public function getCompletedHistory(User $staff): Collection
    {
        return Ticket::with(['wave', 'room'])
            ->where('wave_id', $staff->wave_id)
            ->where('room_id', $staff->room_id)
            ->where('status', TicketStatus::COMPLETED)
            ->orderBy('completed_at', 'desc')
            ->get();
    }

    /**
     * Menghapus tiket dari tampilan riwayat (COMPLETED -> DELETED).
     */
    public function deleteHistoryTicket(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $lockedTicket = Ticket::where('id', $ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTicket->status !== TicketStatus::COMPLETED) {
                throw new RuntimeException('Hanya tiket pada riwayat selesai (COMPLETED) yang dapat dihapus.');
            }

            $lockedTicket->update([
                'status' => TicketStatus::DELETED,
            ]);
        });
    }
}