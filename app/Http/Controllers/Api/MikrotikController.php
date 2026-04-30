<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DisconnectUserRequest;
use App\Services\MikrotikService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MikrotikController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MikrotikService $mikrotikService
    ) {}

    public function activeUsers(): JsonResponse
    {
        try {
            $users = cache()->remember('mikrotik_active_users', 30, function () {
                return $this->mikrotikService->getActiveUsers();
            });

            return $this->successResponse([
                'count' => count($users),
                'users' => $users,
            ], 'Data user aktif Mikrotik berhasil diambil.');

        } catch (Exception $e) {
            Log::error('MikrotikController@activeUsers: '.$e->getMessage());

            return $this->errorResponse(
                'Gagal terhubung ke Mikrotik: '.$e->getMessage(),
                null,
                503
            );
        }
    }

    public function disconnect(DisconnectUserRequest $request): JsonResponse
    {
        $username = $request->validated('username');

        try {
            $this->mikrotikService->disconnectUser($username);
            cache()->forget('mikrotik_active_users');

            return $this->successResponse(
                ['username' => $username],
                "User '{$username}' berhasil di-disconnect dari Mikrotik."
            );

        } catch (Exception $e) {
            Log::error("MikrotikController@disconnect [{$username}]: ".$e->getMessage());

            if (str_contains($e->getMessage(), 'tidak ditemukan')) {
                return $this->errorResponse($e->getMessage(), null, 404);
            }

            return $this->errorResponse(
                'Gagal disconnect user: '.$e->getMessage(),
                null,
                503
            );
        }
    }

    public function ping(): JsonResponse
    {
        try {
            $this->mikrotikService->connect();

            return $this->successResponse([
                'host' => config('mikrotik.host'),
                'port' => config('mikrotik.port'),
                'status' => 'connected',
            ], 'Koneksi Mikrotik berhasil.');

        } catch (Exception $e) {
            return $this->errorResponse(
                'Koneksi Mikrotik gagal: '.$e->getMessage(),
                [
                    'host' => config('mikrotik.host'),
                    'port' => config('mikrotik.port'),
                ],
                503
            );
        }
    }
}
