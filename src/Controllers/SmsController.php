<?php
namespace App\Controllers;

use App\Logger;
use App\Queue;
use PDO;

class SmsController {
    private PDO $db;
    private Logger $log;
    private array $config;

    public function __construct(PDO $db, Logger $log, array $config) {
        $this->db = $db; $this->log = $log; $this->config = $config;
    }

    public function send(array $user): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $to = trim($input['to'] ?? '');
        $message = trim($input['message'] ?? '');

        if (!$to || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'message' => 'to and message are required']);
            return;
        }
        if (!preg_match('/^\+?\d{10,15}$/', $to)) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_number', 'message' => 'recipient format is invalid']);
            return;
        }

        $queue = new Queue($this->db, $this->log, $this->config);
        $msgId = $queue->enqueue((int)$user['id'], $to, $message);

        $resp = [
            'id' => $msgId,
            'status' => 'queued',
            'timestamp' => date('c'),
        ];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    }

    public function bulk(array $user): void {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $list = $input['to'] ?? [];
        $message = trim($input['message'] ?? '');

        if (!is_array($list) || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'message' => 'to[] and message are required']);
            return;
        }

        // Basit sınır: en fazla 100 alıcı
        if (count($list) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'too_many_recipients', 'message' => 'max 100 recipients per request']);
            return;
        }

        $queue = new Queue($this->db, $this->log, $this->config);
        $ids = [];
        foreach ($list as $to) {
            $to = trim((string)$to);
            if (!preg_match('/^\+?\d{10,15}$/', $to)) {
                // Geçersiz numarayı atla; isterseniz tamamen reddedebilirsiniz.
                continue;
            }
            $ids[] = $queue->enqueue((int)$user['id'], $to, $message);
        }

        if (!$ids) {
            http_response_code(400);
            echo json_encode(['error' => 'no_valid_recipients']);
            return;
        }

        $resp = [
            'ids' => $ids,
            'status' => 'queued',
            'timestamp' => date('c'),
        ];
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    }
}