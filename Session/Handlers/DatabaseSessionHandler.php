<?php

declare(strict_types=1);

/**
 * ==================================
 * SessionHandler ===================
 * ==================================
 */

namespace Ace\ace\Session\Handlers;

use PDO;
use Ace\ace\Session\SessionHandler;

class DatabaseSessionHandler extends SessionHandler
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
        $this->start();
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($sessionId)
    {
        $stmt = $this->pdo->prepare('SELECT data FROM sessions WHERE id = :id');
        $stmt->execute([':id' => $sessionId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return base64_decode($row['data']);
        }
        return '';
    }

    public function write($sessionId, $data)
    {
        $stmt = $this->pdo->prepare('REPLACE INTO sessions (id, data, timestamp) VALUES (:id, :data, :timestamp)');
        return $stmt->execute([
            ':id' => $sessionId,
            ':data' => base64_encode($data),
            ':timestamp' => time(),
        ]);
    }

    public function destroy_session($sessionId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = :id');
        return $stmt->execute([':id' => $sessionId]);
    }

    public function gc($maxLifetime)
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE timestamp < :timestamp');
        return $stmt->execute([':timestamp' => time() - $maxLifetime]);
    }
}