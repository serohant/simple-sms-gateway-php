<?php
namespace App;

use PDO;

class Auth {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function validate(string $apiKey): ?array {
        if (!$apiKey) return null;
        $stmt = $this->db->prepare("SELECT * FROM users WHERE api_key = :k AND status='active' LIMIT 1");
        $stmt->execute([':k' => $apiKey]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}