<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RadiusService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly RadiusService $radiusService
    ) {}

    public function online(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 15);
            $perPage = max(5, min(100, $perPage));

            $sessions = $this->radiusService->getActiveSessions($perPage);

            $transformedItems = collect($sessions->items())->map(function ($session) {
                return [
                    'id' => $session->radacctid,
                    'username' => $session->username,
                    'ip_address' => $session->framedipaddress,
                    'nas_ip' => $session->nasipaddress,
                    'mac_address' => $session->callingstationid,
                    'start_time' => $session->acctstarttime?->toIso8601String(),
                    'duration' => $session->session_duration,
                    'data_upload' => $this->formatBytes((int) ($session->acctinputoctets ?? 0)),
                    'data_download' => $this->formatBytes((int) ($session->acctoutputoctets ?? 0)),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Sesi aktif berhasil diambil.',
                'data' => $transformedItems,
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('SessionController@online: '.$e->getMessage());

            return $this->errorResponse('Gagal mengambil data sesi aktif.', null, 500);
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), $precision).' '.$units[$i];
    }
}
