<?php
// public/index.php
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/RateLimiter.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/Queue.php';
require_once __DIR__ . '/../src/Controllers/SmsController.php';
require_once __DIR__ . '/../src/Controllers/StatusController.php';

use App\Controllers\SmsController;
use App\Controllers\StatusController;
use App\Auth;
use App\RateLimiter;
use App\Logger;

header('Content-Type: application/json; charset=utf-8');

// Basit router
$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$db  = App\DB::get($config);
$log = new Logger($config['log_file'] ?? __DIR__ . '/../sms.log');
$auth = new Auth($db);
$rl = new RateLimiter($db, (int)($config['rate_limit_per_minute'] ?? 60));

// API Key doğrulama (status için de gerekli kılalım)
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$user = $auth->validate($apiKey);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_api_key']);
    exit;
}

// Rate limit (GET status hariç tutmak isterseniz metod bazlı esnetebilirsiniz)
if (!($method === 'GET' && str_starts_with($route, 'sms/status'))) {
    if (!$rl->allow($user['id'])) {
        http_response_code(429);
        echo json_encode(['error' => 'rate_limit_exceeded', 'message' => 'Too many requests. Try again later.']);
        exit;
    }
}

try {
    if ($method === 'POST' && $route === 'sms/send') {
        (new SmsController($db, $log, $config))->send($user);
    } elseif ($method === 'POST' && $route === 'sms/bulk') {
        (new SmsController($db, $log, $config))->bulk($user);
    } elseif ($method === 'GET' && $route === 'sms/status') {
        (new StatusController($db, $log))->status($user);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'not_found', 'route' => $route]);
    }
} catch (Throwable $e) {
    $log->write('error', ['exception' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'internal_error']);
}