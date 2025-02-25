<?php

namespace App\Services;

interface CacheManagerInterface
{
    /**
     * Retrieve an item from the cache
     *
     * @param string $key Cache key
     * @return mixed|null Returns cached value or null if not found
     */
    public function get(string $key);

    /**
     * Store an item in the cache for a given time
     *
     * @param string $key Cache key
     * @param mixed $value Value to store
     * @param int $ttl Time to live in seconds (default: 1 hour)
     * @return bool True on success, false on failure
     */
    public function set(string $key, $value, int $ttl = 3600): bool;

    /**
     * Check if an item exists in the cache
     *
     * @param string $key Cache key
     * @return bool True if exists, false otherwise
     */
    public function has(string $key): bool;

    /**
     * Remove an item from the cache
     *
     * @param string $key Cache key
     * @return bool True if removed, false otherwise
     */
    public function delete(string $key): bool;

    /**
     * Clear all items from the cache
     *
     * @return bool True on success, false on failure
     */
    public function clearCache(): bool;

    /**
     * Check if the cache service is available
     *
     * @return bool True if available, false otherwise
     */
    public function isAvailable(): bool;
}
