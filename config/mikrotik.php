<?php

return [
    'host' => env('MIKROTIK_HOST', '192.168.1.1'),
    'port' => (int) env('MIKROTIK_PORT', 8728),
    'user' => env('MIKROTIK_USER', 'admin'),
    'password' => env('MIKROTIK_PASSWORD', ''),
    'timeout' => (int) env('MIKROTIK_TIMEOUT', 10),
];
