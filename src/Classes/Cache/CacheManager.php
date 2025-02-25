<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

class CacheManager
{
    private $redis;
    private $isAvailable = false;
    private $cacheDir;

    public function __construct()
    {
        $this->cacheDir = __DIR__ . '/../../../storage/cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        try {
            $this->redis = new \Predis\Client([
                'scheme' => 'tcp',
                'host' => 'localhost',
                'port' => 6379
            ]);

            // Test connection
            $this->redis->ping();
            $this->isAvailable = true;
        } catch (\Exception $e) {
            error_log("Redis connection failed: " . $e->getMessage());
            $this->isAvailable = false;
        }
    }

    public function get(string $key)
    {
        if ($this->isAvailable) {
            try {
                $value = $this->redis->get($key);
                return $value ? json_decode($value, true) : null;
            } catch (Exception $e) {
                error_log("Redis get failed: " . $e->getMessage());
            }
        }

        return $this->getFromFile($key);
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        if ($this->isAvailable) {
            try {
                return $this->redis->setex($key, $ttl, json_encode($value)) == 'OK';
            } catch (Exception $e) {
                error_log("Redis set failed: " . $e->getMessage());
            }
        }

        return $this->setToFile($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        if ($this->isAvailable) {
            try {
                return $this->redis->del($key) > 0;
            } catch (Exception $e) {
                error_log("Redis delete error: " . $e->getMessage());
            }
        }

        return $this->deleteFileCache($key);
    }

    private function getFromFile(string $key)
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        if (!$data || $data['expires'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    private function setToFile(string $key, $value, int $ttl): bool
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];

        return file_put_contents($file, json_encode($data)) !== false;
    }

    private function deleteFileCache(string $key): bool
    {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return false;
    }

    private function getCacheFilePath(string $key): string
    {
        $dir = __DIR__ . '/../../../storage/cache/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir . md5($key) . '.cache';
    }
}