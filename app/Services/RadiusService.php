<?php

namespace App\Services;

use App\Exceptions\UserAlreadyExistsException;
use App\Exceptions\UserNotFoundException;
use App\Models\RadAcct;
use App\Models\RadCheck;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusService
{
    /**
     * Ambil semua user dari radcheck dengan pagination
     */
    public function getAllUsers(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $query = RadCheck::passwordOnly()
            ->select('id', 'username', 'attribute', 'op')
            ->orderBy('username');

        if ($search) {
            $query->search($search);
        }

        return $query->paginate($perPage);
    }

    /**
     * Cek apakah user sudah ada
     */
    public function userExists(string $username): bool
    {
        return RadCheck::where('username', $username)
            ->where('attribute', 'Cleartext-Password')
            ->exists();
    }

    /**
     * Buat user baru di radcheck
     */
    public function createUser(string $username, string $password): RadCheck
    {
        if ($this->userExists($username)) {
            throw new UserAlreadyExistsException($username);
        }

        return DB::connection('radius')->transaction(function () use ($username, $password) {
            $user = RadCheck::create([
                'username' => $username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $password,
            ]);

            cache()->forget('dashboard_stats');
            Log::info("User RADIUS dibuat: {$username}");

            return $user;
        });
    }

    /**
     * Hapus user dari radcheck
     */
    public function deleteUser(string $username): bool
    {
        if (! $this->userExists($username)) {
            throw new UserNotFoundException($username);
        }

        DB::connection('radius')->transaction(function () use ($username) {
            RadCheck::where('username', $username)->delete();

            DB::connection('radius')
                ->table('radreply')
                ->where('username', $username)
                ->delete();

            cache()->forget('dashboard_stats');
            Log::info("User RADIUS dihapus: {$username}");
        });

        return true;
    }

    /**
     * Ambil sesi aktif (acctstoptime IS NULL)
     */
    public function getActiveSessions(int $perPage = 15): LengthAwarePaginator
    {
        return RadAcct::active()
            ->select([
                'radacctid',
                'username',
                'nasipaddress',
                'framedipaddress',
                'acctstarttime',
                'acctsessiontime',
                'acctinputoctets',
                'acctoutputoctets',
                'callingstationid',
            ])
            ->orderBy('acctstarttime', 'desc')
            ->paginate($perPage);
    }

    /**
     * Ambil statistik dashboard (dengan cache)
     */
    public function getDashboardStats(): array
    {
        return cache()->remember('dashboard_stats', 60, function () {
            return [
                'total_users' => RadCheck::passwordOnly()->count(),
                'active_sessions' => RadAcct::active()->count(),
                'total_sessions' => RadAcct::count(),
                'today_sessions' => RadAcct::whereDate('acctstarttime', today())->count(),
            ];
        });
    }
}
