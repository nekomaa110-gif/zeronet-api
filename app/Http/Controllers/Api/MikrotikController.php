<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MikrotikConnectionException;
use App\Exceptions\UserNotFoundException;
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

        } catch (MikrotikConnectionException $e) {
            Log::error('MikrotikController@activeUsers: '.$e->getMessage());

            return $this->errorResponse($e->getMessage(), null, 503);
        } catch (Exception $e) {
            Log::error('MikrotikController@activeUsers: '.$e->getMessage());

            return $this->errorResponse('Gagal mengambil data Mikrotik.', null, 500);
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

        } catch (UserNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), null, 404);
        } catch (MikrotikConnectionException $e) {
            Log::error("MikrotikController@disconnect [{$username}]: ".$e->getMessage());

            return $this->errorResponse($e->getMessage(), null, 503);
        } catch (Exception $e) {
            Log::error("MikrotikController@disconnect [{$username}]: ".$e->getMessage());

            return $this->errorResponse('Gagal disconnect user.', null, 500);
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

        } catch (MikrotikConnectionException|Exception $e) {
            return $this->errorResponse(
                'Koneksi Mikrotik gagal: '.$e->getMessage(),
                ['host' => config('mikrotik.host'), 'port' => config('mikrotik.port')],
                503
            );
        }
    }
}
