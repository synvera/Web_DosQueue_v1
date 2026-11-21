<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login petugas dan menghasilkan Sanctum Bearer Token.
     */
    public function login(StaffLoginRequest $request): JsonResponse
    {
        $user = User::with(['wave', 'room'])
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
                'data' => null,
            ], 401);
        }

        // Pastikan petugas sudah dipetakan ke gelombang dan ruangan
        if (! $user->wave_id || ! $user->room_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun petugas belum memiliki penugasan gelombang atau ruangan.',
                'data' => null,
            ], 403);
        }

        // Hapus token lama agar satu petugas memiliki sesi yang bersih (opsional untuk prototype)
        $user->tokens()->delete();

        $token = $user->createToken('staff-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login petugas berhasil.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'staff' => $this->formatStaffData($user),
            ],
        ], 200);
    }

    /**
     * Mengambil informasi petugas yang sedang login beserta penugasan gelombang & ruangan.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['wave', 'room']);

        return response()->json([
            'success' => true,
            'message' => 'Data petugas berhasil diambil.',
            'data' => $this->formatStaffData($user),
        ], 200);
    }

    /**
     * Logout petugas (menghapus token saat ini).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
            'data' => null,
        ], 200);
    }

    /**
     * Helper format struktur data petugas yang konsisten.
     */
    private function formatStaffData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'wave' => $user->wave ? [
                'id' => $user->wave->id,
                'name' => $user->wave->name,
                'code' => $user->wave->code,
            ] : null,
            'room' => $user->room ? [
                'id' => $user->room->id,
                'name' => $user->room->name,
                'code' => $user->room->code,
            ] : null,
        ];
    }
}