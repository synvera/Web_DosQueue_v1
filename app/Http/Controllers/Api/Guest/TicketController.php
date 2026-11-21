<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}

    /**
     * Membuat tiket baru untuk pengunjung (atau mengembalikan tiket aktif jika sudah ada).
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $existingToken = $request->header('X-Ticket-Token') ?? $request->validated('current_token');

        try {
            $result = $this->ticketService->createTicket(
                waveId: (int) $request->validated('wave_id'),
                existingToken: $existingToken
            );

            if ($result['is_existing']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Anda masih memiliki tiket aktif. Dialihkan ke tiket sebelumnya.',
                    'data' => new TicketResource($result['ticket']),
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tiket berhasil dibuat.',
                'data' => new TicketResource($result['ticket']),
            ], 201);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }
    }

    /**
     * Memeriksa apakah pengunjung masih memiliki tiket aktif (WAITING / CALLED) berdasarkan token.
     */
    public function active(Request $request): JsonResponse
    {
        $token = $request->header('X-Ticket-Token') ?? $request->query('token');
        $activeTicket = $this->ticketService->getActiveTicketByToken($token);

        if (! $activeTicket) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada tiket aktif. Pengunjung dapat mengambil tiket baru.',
                'data' => [
                    'has_active_ticket' => false,
                    'ticket' => null,
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tiket aktif ditemukan.',
            'data' => [
                'has_active_ticket' => true,
                'ticket' => new TicketResource($activeTicket),
            ],
        ], 200);
    }

    /**
     * Mengambil informasi detail tiket berdasarkan token unik.
     */
    public function show(string $token): JsonResponse
    {
        $ticket = $this->ticketService->getTicketByToken($token);

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan atau sudah dihapus.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Informasi tiket berhasil diambil.',
            'data' => new TicketResource($ticket),
        ], 200);
    }

    /**
     * Endpoint ringan untuk Short Polling (3-5 detik) status antrian pengunjung.
     */
    public function monitor(string $token): JsonResponse
    {
        $ticket = $this->ticketService->getTicketByToken($token);

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan atau sudah dihapus.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data monitor antrian berhasil diambil.',
            'data' => $this->ticketService->getTicketMonitorData($ticket),
        ], 200);
    }
}