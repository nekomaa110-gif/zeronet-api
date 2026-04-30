<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Services\RadiusService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RadiusService $radiusService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $search = $request->query('search');
            $perPage = max(5, min(100, $perPage));

            $users = $this->radiusService->getAllUsers($perPage, $search);

            return $this->paginatedResponse($users, 'Daftar user berhasil diambil.');

        } catch (Exception $e) {
            Log::error('UserController@index: '.$e->getMessage());

            return $this->errorResponse('Gagal mengambil data user.', null, 500);
        }
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->radiusService->createUser(
                $request->validated('username'),
                $request->validated('password')
            );

            return $this->successResponse([
                'id' => $user->id,
                'username' => $user->username,
                'attribute' => $user->attribute,
            ], 'User berhasil dibuat.', 201);

        } catch (Exception $e) {
            Log::error('UserController@store: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'sudah ada')) {
                return $this->errorResponse($e->getMessage(), null, 409);
            }

            return $this->errorResponse('Gagal membuat user.', null, 500);
        }
    }

    public function destroy(string $username): JsonResponse
    {
        try {
            $this->radiusService->deleteUser($username);

            return $this->successResponse(null, "User '{$username}' berhasil dihapus.");

        } catch (Exception $e) {
            Log::error('UserController@destroy: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'tidak ditemukan')) {
                return $this->errorResponse($e->getMessage(), null, 404);
            }

            return $this->errorResponse('Gagal menghapus user.', null, 500);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $stats = $this->radiusService->getDashboardStats();

            return $this->successResponse($stats, 'Statistik berhasil diambil.');

        } catch (Exception $e) {
            Log::error('UserController@stats: '.$e->getMessage());

            return $this->errorResponse('Gagal mengambil statistik.', null, 500);
        }
    }
}
