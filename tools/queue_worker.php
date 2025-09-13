<?php
// tools/queue_worker.php
declare(strict_types=1);
$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Logger.php';
require_once __DIR__ . '/../src/Queue.php';

$db  = App\DB::get($config);
$log = new App\Logger($config['log_file'] ?? __DIR__ . '/../sms.log');
$q   = new App\Queue($db, $log, $config);

$processed = $q->processOnce();
echo "Processed: {$processed}\n";
