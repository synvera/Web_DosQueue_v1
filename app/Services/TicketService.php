<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\Wave;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketService
{
    public function __construct(
        private RoundRobinService $roundRobinService
    ) {}

    /**
     * Membuat tiket baru dengan jaminan keamanan kuota, Round-Robin, dan proteksi tiket ganda.
     *
     * @return array{ticket: Ticket, is_existing: bool}
     */
    public function createTicket(int $waveId, ?string $existingToken = null): array
    {
        if (! empty($existingToken)) {
            $activeTicket = $this->getActiveTicketByToken($existingToken);

            if ($activeTicket) {
                return [
                    'ticket' => $activeTicket,
                    'is_existing' => true,
                ];
            }
        }

        return DB::transaction(function () use ($waveId) {
            $wave = Wave::where('id', $waveId)->lockForUpdate()->first();

            if (! $wave) {
                throw new RuntimeException('Gelombang tidak ditemukan.');
            }

            if (! $wave->is_active) {
                throw new RuntimeException('Gelombang yang dipilih sedang tidak aktif.');
            }

            $usedQuota = Ticket::where('wave_id', $wave->id)->count();

            if ($usedQuota >= $wave->max_quota) {
                throw new RuntimeException('Kuota gelombang sudah habis.');
            }

            $lastSequence = (int) Ticket::where('wave_id', $wave->id)->max('sequence_number');
            $nextSequence = $lastSequence + 1;

            $assignedRoom = $this->roundRobinService->getNextRoom($wave->id);

            $formattedSequence = str_pad((string) $nextSequence, 3, '0', STR_PAD_LEFT);
            $ticketNumber = "{$wave->code}-{$assignedRoom->code}-{$formattedSequence}";
            $token = $this->generateUniqueToken();

            $ticket = Ticket::create([
                'token' => $token,
                'ticket_number' => $ticketNumber,
                'wave_id' => $wave->id,
                'room_id' => $assignedRoom->id,
                'sequence_number' => $nextSequence,
                'status' => TicketStatus::WAITING,
            ]);

            $ticket->load(['wave', 'room']);

            return [
                'ticket' => $ticket,
                'is_existing' => false,
            ];
        });
    }

    /**
     * Mencari tiket yang masih aktif (WAITING atau CALLED) berdasarkan token.
     */
    public function getActiveTicketByToken(?string $token): ?Ticket
    {
        if (empty($token)) {
            return null;
        }

        return Ticket::with(['wave', 'room'])
            ->where('token', $token)
            ->whereIn('status', TicketStatus::activeStatuses())
            ->first();
    }

    /**
     * Mengambil detail tiket berdasarkan token (selama belum DELETED).
     */
    public function getTicketByToken(string $token): ?Ticket
    {
        return Ticket::with(['wave', 'room'])
            ->where('token', $token)
            ->where('status', '!=', TicketStatus::DELETED->value)
            ->first();
    }

    /**
     * Mengambil data ringan untuk kebutuhan Short Polling (3-5 detik) monitor antrian pengunjung.
     */
    public function getTicketMonitorData(Ticket $ticket): array
    {
        // Cari tiket yang sedang dipanggil (CALLED) di ruangan dan gelombang yang sama
        $currentCalledTicket = Ticket::where('wave_id', $ticket->wave_id)
            ->where('room_id', $ticket->room_id)
            ->where('status', TicketStatus::CALLED)
            ->first();

        // Hitung berapa banyak antrian WAITING di ruangan yang sama sebelum tiket pengunjung ini
        $queuesAhead = 0;
        if ($ticket->status === TicketStatus::WAITING) {
            $queuesAhead = Ticket::where('wave_id', $ticket->wave_id)
                ->where('room_id', $ticket->room_id)
                ->where('status', TicketStatus::WAITING)
                ->where('sequence_number', '<', $ticket->sequence_number)
                ->count();
        }

        return [
            'ticket_number' => $ticket->ticket_number,
            'status' => $ticket->status->value,
            'is_active' => $ticket->isActive(),
            'wave' => [
                'id' => $ticket->wave->id,
                'name' => $ticket->wave->name,
                'code' => $ticket->wave->code,
            ],
            'room' => [
                'id' => $ticket->room->id,
                'name' => $ticket->room->name,
                'code' => $ticket->room->code,
            ],
            'current_serving_ticket' => $currentCalledTicket?->ticket_number,
            'queues_ahead' => $queuesAhead,
            'called_at' => $ticket->called_at?->toIso8601String(),
            'completed_at' => $ticket->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Menghasilkan token unik dengan prefix tkt_.
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = 'tkt_' . bin2hex(random_bytes(16));
        } while (Ticket::where('token', $token)->exists());

        return $token;
    }
}