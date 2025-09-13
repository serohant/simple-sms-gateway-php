<?php
namespace App\Controllers;

use App\Logger;
use PDO;

class StatusController {
    private PDO $db;
    private Logger $log;

    public function __construct(PDO $db, Logger $log) {
        $this->db = $db; $this->log = $log;
    }

    public function status(array $user): void {
        $id = $_GET['id'] ?? '';
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid_request', 'message' => 'id required']);
            return;
        }
        $stmt = $this->db->prepare("SELECT msg_id, status, created_at, updated_at FROM messages WHERE msg_id=:i AND user_id=:u LIMIT 1");
        $stmt->execute([':i'=>$id, ':u'=>(int)$user['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found', 'message' => 'message id not found']);
            return;
        }
        echo json_encode([
            'id' => $row['msg_id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ], JSON_UNESCAPED_UNICODE);
    }
}
