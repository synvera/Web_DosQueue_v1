<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class QueueController extends Controller
{
    public function __construct(
        private QueueService $queueService
    ) {}

    /**
     * Menampilkan daftar antrian aktif (FIFO) sesuai gelombang dan ruangan petugas.
     */
    public function index(Request $request): JsonResponse
    {
        $queues = $this->queueService->getActiveQueues($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Daftar antrian aktif berhasil diambil.',
            'data' => TicketResource::collection($queues),
        ], 200);
    }

    /**
     * Memanggil pengunjung berikutnya (WAITING -> CALLED) dan mengembalikan data TTS.
     */
    public function call(Ticket $ticket): JsonResponse
    {
        Gate::authorize('manage', $ticket);

        try {
            $updatedTicket = $this->queueService->callTicket($ticket);

            return response()->json([
                'success' => true,
                'message' => "Memanggil nomor antrian {$updatedTicket->ticket_number} ke {$updatedTicket->room->name}.",
                'data' => [
                    'ticket' => new TicketResource($updatedTicket),
                    // Informasi khusus untuk pemicu Text-to-Speech (TTS) di frontend
                    'tts' => [
                        'ticket_number' => $updatedTicket->ticket_number,
                        'room_name' => $updatedTicket->room->name,
                        'announcement_text' => "Nomor antrian {$updatedTicket->ticket_number}, silakan menuju {$updatedTicket->room->name}.",
                    ],
                ],
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }

    /**
     * Menyelesaikan pelayanan tiket (CALLED -> COMPLETED).
     */
    public function complete(Ticket $ticket): JsonResponse
    {
        Gate::authorize('manage', $ticket);

        try {
            $updatedTicket = $this->queueService->completeTicket($ticket);

            return response()->json([
                'success' => true,
                'message' => "Pelayanan tiket {$updatedTicket->ticket_number} telah selesai.",
                'data' => new TicketResource($updatedTicket),
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }

    /**
     * Menampilkan daftar riwayat tiket yang sudah selesai (COMPLETED).
     */
    public function history(Request $request): JsonResponse
    {
        $history = $this->queueService->getCompletedHistory($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pelayanan selesai berhasil diambil.',
            'data' => TicketResource::collection($history),
        ], 200);
    }

    /**
     * Menghapus tiket dari daftar riwayat (COMPLETED -> DELETED).
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        Gate::authorize('manage', $ticket);

        try {
            $this->queueService->deleteHistoryTicket($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Data tiket berhasil dihapus dari riwayat.',
                'data' => null,
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }
}