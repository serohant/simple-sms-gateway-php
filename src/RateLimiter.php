<?php
namespace App;

use PDO;
use DateTimeImmutable;
use DateTimeZone;

class RateLimiter {
    private PDO $db;
    private int $perMinute;

    public function __construct(PDO $db, int $perMinute) {
        $this->db = $db;
        $this->perMinute = max(1, $perMinute);
    }

    public function allow(int $userId): bool {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $windowStart = $now->format('Y-m-d H:i:00'); // minute window

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id, counter FROM rate_limits WHERE user_id=:u AND window_start=:w LIMIT 1 FOR UPDATE");
            $stmt->execute([':u'=>$userId, ':w'=>$windowStart]);
            $row = $stmt->fetch();

            if (!$row) {
                $ins = $this->db->prepare("INSERT INTO rate_limits (user_id, window_start, counter) VALUES (:u, :w, 1)");
                $ins->execute([':u'=>$userId, ':w'=>$windowStart]);
                $this->db->commit();
                return true;
            }

            if ((int)$row['counter'] < $this->perMinute) {
                $upd = $this->db->prepare("UPDATE rate_limits SET counter = counter + 1 WHERE id=:id");
                $upd->execute([':id'=>$row['id']]);
                $this->db->commit();
                return true;
            }

            $this->db->commit();
            return false;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}