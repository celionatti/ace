<?php

namespace App\Middlewares;

use Ace\Application;

class RateLimitMiddleware extends BaseMiddleware
{
    private int $limit = 60; // 60 requests
    private int $window = 60; // per 60 seconds

    protected function run(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cacheDir = Application::$ROOT_DIR . '/storage/cache';
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        $cacheFile = $cacheDir . '/rate_limits.json';
        $data = [];
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true) ?? [];
        }

        $now = time();
        
        // Cleanup old entries
        foreach ($data as $cacheIp => $info) {
            if ($now - $info['time'] > $this->window) {
                unset($data[$cacheIp]);
            }
        }

        if (isset($data[$ip])) {
            $data[$ip]['count']++;
            if ($data[$ip]['count'] > $this->limit) {
                // Save state and block request
                file_put_contents($cacheFile, json_encode($data));
                throw new \Exception("Too Many Requests", 429);
            }
        } else {
            $data[$ip] = [
                'count' => 1,
                'time' => $now
            ];
        }

        file_put_contents($cacheFile, json_encode($data));
    }
}

