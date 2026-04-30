<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RadiusService;
use App\Traits\ApiResponse;
use App\Traits\FormatBytes;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    use ApiResponse, FormatBytes;

    public function __construct(
        private readonly RadiusService $radiusService
    ) {}

    public function online(Request $request): JsonResponse
    {
        try {
            $perPage = max(5, min(100, (int) $request->query('per_page', 15)));

            $sessions = $this->radiusService->getActiveSessions($perPage);

            $sessions->through(fn ($session) => [
                'id' => $session->radacctid,
                'username' => $session->username,
                'ip_address' => $session->framedipaddress,
                'nas_ip' => $session->nasipaddress,
                'mac_address' => $session->callingstationid,
                'start_time' => $session->acctstarttime?->toIso8601String(),
                'duration' => $session->session_duration,
                'data_upload' => $this->formatBytes((int) ($session->acctinputoctets ?? 0)),
                'data_download' => $this->formatBytes((int) ($session->acctoutputoctets ?? 0)),
            ]);

            return $this->paginatedResponse($sessions, 'Sesi aktif berhasil diambil.');

        } catch (Exception $e) {
            Log::error('SessionController@online: '.$e->getMessage());

            return $this->errorResponse('Gagal mengambil data sesi aktif.', null, 500);
        }
    }
}
