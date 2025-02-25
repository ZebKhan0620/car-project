<?php

namespace App\Services;

use Predis\Client;
use Exception;

class CacheManager implements CacheManagerInterface
{
    private $redis;
    private const DEFAULT_TTL = 3600; // 1 hour
    private const RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY = 100; // milliseconds

    public function __construct()
    {
        try {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
                'timeout' => 1.0,
                'read_write_timeout' => 1.0,
                'retry_interval' => 100
            ]);
            
            // Test connection
            $this->redis->ping();
        } catch (Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            // Fall back to null - operations will fail gracefully
            $this->redis = null;
        }
    }

    public function get(string $key)
    {
        if (!$this->redis) return null;

        try {
            $value = $this->redis->get($key);
            return $value ? json_decode($value, true) : null;
        } catch (Exception $e) {
            error_log("Redis get error: " . $e->getMessage());
            return null;
        }
    }

    public function set(string $key, $value, int $ttl = self::DEFAULT_TTL): bool
    {
        if (!$this->redis) return false;

        try {
            for ($i = 0; $i < self::RETRY_ATTEMPTS; $i++) {
                try {
                    $this->redis->setex(
                        $key,
                        $ttl,
                        json_encode($value, JSON_THROW_ON_ERROR)
                    );
                    return true;
                } catch (Exception $e) {
                    if ($i === self::RETRY_ATTEMPTS - 1) throw $e;
                    usleep(self::RETRY_DELAY * 1000);
                }
            }
        } catch (Exception $e) {
            error_log("Redis set error: " . $e->getMessage());
            return false;
        }
        return false;
    }

    public function has(string $key): bool
    {
        if (!$this->redis) return false;

        try {
            return (bool) $this->redis->exists($key);
        } catch (Exception $e) {
            error_log("Redis exists error: " . $e->getMessage());
            return false;
        }
    }

    public function delete(string $key): bool
    {
        if (!$this->redis) return false;

        try {
            return (bool) $this->redis->del([$key]);
        } catch (Exception $e) {
            error_log("Redis delete error: " . $e->getMessage());
            return false;
        }
    }

    public function clearCache(): bool
    {
        if (!$this->redis) return false;

        try {
            $this->redis->flushdb();
            return true;
        } catch (Exception $e) {
            error_log("Redis flush error: " . $e->getMessage());
            return false;
        }
    }

    public function isAvailable(): bool
    {
        if (!$this->redis) return false;

        try {
            $this->redis->ping();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
