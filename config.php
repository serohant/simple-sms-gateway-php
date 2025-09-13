<?php
// config.php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'sms_api',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4'
    ],
    // dakika bazlı rate limit
    'rate_limit_per_minute' => 30,
    // log dosyası
    'log_file' => __DIR__ . '/sms.log',
    // mesaj ID formatı için yıl (otomatik de alınır)
    'id_year' => (int)date('Y'),
];