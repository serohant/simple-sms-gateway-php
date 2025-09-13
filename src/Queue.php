<?php
namespace App;

use PDO;

class Queue {
    private PDO $db;
    private Logger $log;
    private array $config;
    public function __construct(PDO $db, Logger $log, array $config) {
        $this->db = $db; $this->log = $log; $this->config = $config;
    }

    // Benzersiz ID üret
    public function nextId(): string {
        $year = (int)($this->config['id_year'] ?? date('Y'));
        // Yıl bazında sıralı numara: messages tablosunda o yıl kaç kayıt var?
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM messages WHERE msg_id LIKE :p");
        $stmt->execute([':p' => "MSG-{$year}-%"]);
        $c = (int)$stmt->fetch()['c'] + 1;
        return sprintf("MSG-%d-%06d", $year, $c);
    }

    public function enqueue(int $userId, string $recipient, string $content): string {
        $msgId = $this->nextId();
        $ins = $this->db->prepare("INSERT INTO messages (msg_id, user_id, recipient, content, status) VALUES (:i,:u,:r,:c,'queued')");
        $ins->execute([':i'=>$msgId, ':u'=>$userId, ':r'=>$recipient, ':c'=>$content]);
        $this->logStatus(null, 'queued', $msgId);
        return $msgId;
    }

    public function logStatus(?string $old, string $new, string $msgId): void {
        $stmt = $this->db->prepare("INSERT INTO message_status_logs (msg_id, old_status, new_status) VALUES (:m,:o,:n)");
        $stmt->execute([':m'=>$msgId, ':o'=>$old, ':n'=>$new]);
    }

    // Simülasyon: queued -> sent veya failed; sent -> delivered
    public function processOnce(): int {
        $stmt = $this->db->query("SELECT id, msg_id, status FROM messages WHERE status='queued' ORDER BY id ASC LIMIT 50");
        $rows = $stmt->fetchAll();
        $n = 0;
        foreach ($rows as $r) {
            $ok = $this->sendToProvider($r['msg_id']);
            $new = $ok ? 'sent' : 'failed';
            $upd = $this->db->prepare("UPDATE messages SET status=:s WHERE id=:id");
            $upd->execute([':s'=>$new, ':id'=>$r['id']]);
            $this->logStatus($r['status'], $new, $r['msg_id']);
            $n++;
        }

        // sent -> delivered geçişleri
        $stmt2 = $this->db->query("SELECT id, msg_id, status FROM messages WHERE status='sent' ORDER BY id ASC LIMIT 50");
        $rows2 = $stmt2->fetchAll();
        foreach ($rows2 as $r) {
            if (mt_rand(0, 100) < 80) { // %80 delivered varsayalım
                $upd = $this->db->prepare("UPDATE messages SET status='delivered' WHERE id=:id");
                $upd->execute([':id'=>$r['id']]);
                $this->logStatus('sent', 'delivered', $r['msg_id']);
                $n++;
            }
        }
        return $n;
    }

    private function sendToProvider(string $msgId): bool {
        // Burada gerçek SMS entegrasyonu yapılabilir.
        // Şimdilik %85 başarı simülasyonu.
        $ok = (mt_rand(0, 100) < 85);
        $this->log->write('provider_sim', ['msg_id' => $msgId, 'ok' => $ok]);
        return $ok;
    }
}