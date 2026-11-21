<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\WaveResource;
use App\Models\Wave;
use Illuminate\Http\JsonResponse;

class WaveController extends Controller
{
    /**
     * Menampilkan daftar gelombang beserta informasi sisa kuota.
     */
    public function index(): JsonResponse
    {
        $waves = Wave::withCount('tickets')
            ->orderBy('id', 'asc')
            ->get();

        $hasAvailableWave = $waves->contains(function (Wave $wave) {
            return $wave->is_active && ($wave->max_quota - $wave->tickets_count) > 0;
        });

        return response()->json([
            'success' => true,
            'message' => $hasAvailableWave
                ? 'Daftar gelombang berhasil diambil.'
                : 'Semua kuota gelombang penuh atau tidak tersedia saat ini.',
            'data' => [
                'can_take_ticket' => $hasAvailableWave,
                'waves' => WaveResource::collection($waves),
            ],
        ], 200);
    }
}