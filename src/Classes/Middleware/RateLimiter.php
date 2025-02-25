<?php
namespace Classes\Middleware;

use Predis\Client;
use Exception;

class RateLimiter {
    private $redis;
    private $maxRequests = 60;
    private $timeWindow = 60; // seconds

    public function __construct() {
        try {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host'   => getenv('REDIS_HOST') ?: '127.0.0.1',
                'port'   => getenv('REDIS_PORT') ?: 6379
            ]);
        } catch (Exception $e) {
            error_log("Rate limiter initialization error: " . $e->getMessage());
        }
    }

    public function check(string $ip): bool {
        try {
            $key = "rate_limit:$ip";
            $current = $this->redis->get($key);

            if (!$current) {
                $this->redis->setex($key, $this->timeWindow, 1);
                return true;
            }

            if ($current >= $this->maxRequests) {
                return false;
            }

            $this->redis->incr($key);
            return true;
        } catch (Exception $e) {
            error_log("Rate limiter error: " . $e->getMessage());
            return true; // Fail open to prevent blocking legitimate traffic
        }
    }
}
