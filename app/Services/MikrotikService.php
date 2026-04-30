<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Query;

class MikrotikService
{
    private ?Client $client = null;

    private array $config;

    public function __construct()
    {
        $this->config = [
            'host' => config('mikrotik.host'),
            'user' => config('mikrotik.user'),
            'pass' => config('mikrotik.password'),
            'port' => config('mikrotik.port'),
            'timeout' => config('mikrotik.timeout'),
        ];
    }

    /**
     * Buat koneksi ke Mikrotik RouterOS API
     *
     * @throws Exception
     */
    public function connect(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        try {
            $this->client = new Client($this->config);

            Log::info('Mikrotik: Koneksi berhasil ke '.$this->config['host']);

            return $this->client;

        } catch (Exception $e) {
            Log::error('Mikrotik: Gagal koneksi - '.$e->getMessage(), [
                'host' => $this->config['host'],
                'port' => $this->config['port'],
            ]);

            throw new Exception(
                'Tidak dapat terhubung ke Mikrotik: '.$e->getMessage()
            );
        }
    }

    /**
     * Ambil daftar user yang sedang aktif dari Mikrotik
     *
     * @throws Exception
     */
    public function getActiveUsers(): array
    {
        try {
            $client = $this->connect();

            // Query ke /ip/hotspot/active untuk hotspot users
            $query = new Query('/ip/hotspot/active/print');
            $response = $client->query($query)->read();

            $users = [];
            foreach ($response as $item) {
                // Skip item yang bukan data user
                if (! isset($item['user'])) {
                    continue;
                }

                $users[] = [
                    'id' => $item['.id'] ?? null,
                    'username' => $item['user'] ?? '',
                    'ip_address' => $item['address'] ?? '',
                    'mac_address' => $item['mac-address'] ?? '',
                    'uptime' => $item['uptime'] ?? '0s',
                    'bytes_in' => $this->formatBytes((int) ($item['bytes-in'] ?? 0)),
                    'bytes_out' => $this->formatBytes((int) ($item['bytes-out'] ?? 0)),
                    'server' => $item['server'] ?? '',
                    'comment' => $item['comment'] ?? '',
                ];
            }

            Log::info('Mikrotik: Berhasil ambil '.count($users).' active users');

            return $users;

        } catch (Exception $e) {
            Log::error('Mikrotik: Gagal ambil active users - '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Disconnect user dari Mikrotik Hotspot
     *
     * @throws Exception
     */
    public function disconnectUser(string $username): bool
    {
        try {
            $client = $this->connect();

            // Cari ID user yang aktif
            $findQuery = (new Query('/ip/hotspot/active/print'))
                ->where('user', $username);

            $activeUsers = $client->query($findQuery)->read();

            if (empty($activeUsers)) {
                throw new Exception("User '{$username}' tidak ditemukan dalam sesi aktif Mikrotik.");
            }

            // Disconnect semua sesi user tersebut
            $disconnected = 0;
            foreach ($activeUsers as $user) {
                if (! isset($user['.id'])) {
                    continue;
                }

                $removeQuery = (new Query('/ip/hotspot/active/remove'))
                    ->equal('.id', $user['.id']);

                $client->query($removeQuery)->read();
                $disconnected++;
            }

            Log::info("Mikrotik: User '{$username}' berhasil di-disconnect ({$disconnected} sesi)");

            return true;

        } catch (Exception $e) {
            Log::error("Mikrotik: Gagal disconnect user '{$username}' - ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Ambil informasi interface Mikrotik
     *
     * @throws Exception
     */
    public function getInterfaces(): array
    {
        try {
            $client = $this->connect();
            $query = new Query('/interface/print');
            $response = $client->query($query)->read();

            return array_map(function ($item) {
                return [
                    'name' => $item['name'] ?? '',
                    'type' => $item['type'] ?? '',
                    'running' => ($item['running'] ?? 'false') === 'true',
                    'comment' => $item['comment'] ?? '',
                ];
            }, array_filter($response, fn ($item) => isset($item['name'])));

        } catch (Exception $e) {
            Log::error('Mikrotik: Gagal ambil interfaces - '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Tutup koneksi Mikrotik
     */
    public function disconnect(): void
    {
        $this->client = null;
        Log::info('Mikrotik: Koneksi ditutup');
    }

    /**
     * Format bytes ke human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Destructor - pastikan koneksi ditutup
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
