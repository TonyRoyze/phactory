<?php

/**
 * Simple File-Based Caching System
 * Provides caching functionality for frequently accessed data
 */
class Cache {
    private static $instance = null;
    private $cacheDir;
    private $defaultTTL;
    
    private function __construct() {
        $this->cacheDir = defined('CACHE_DIR') ? CACHE_DIR : 'cache/';
        $this->defaultTTL = defined('CACHE_LIFETIME') ? CACHE_LIFETIME : 300; // 5 minutes default
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Create .htaccess to prevent direct access
        $htaccessFile = $this->cacheDir . '.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Order deny,allow\nDeny from all");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Cache();
        }
        return self::$instance;
    }
    
    /**
     * Generate cache key from string
     */
    private function generateKey($key) {
        return md5($key);
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->cacheDir . $this->generateKey($key) . '.cache';
    }
    
    /**
     * Store data in cache
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $cacheFile = $this->getCacheFile($key);
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $result = file_put_contents($cacheFile, serialize($cacheData), LOCK_EX);
        return $result !== false;
    }
    
    /**
     * Retrieve data from cache
     */
    public function get($key) {
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cacheData = unserialize(file_get_contents($cacheFile));
        
        if (!$cacheData || !isset($cacheData['expires'])) {
            $this->delete($key);
            return null;
        }
        
        // Check if cache has expired
        if (time() > $cacheData['expires']) {
            $this->delete($key);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * Check if cache key exists and is valid
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Delete cache entry
     */
    public function delete($key) {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        return true;
    }
    
    /**
     * Clear all cache entries
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Clean expired cache entries
     */
    public function cleanup() {
        $files = glob($this->cacheDir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $cacheData = unserialize(file_get_contents($file));
            
            if (!$cacheData || !isset($cacheData['expires']) || time() > $cacheData['expires']) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $totalSize = 0;
        $validEntries = 0;
        $expiredEntries = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $cacheData = unserialize(file_get_contents($file));
            
            if ($cacheData && isset($cacheData['expires'])) {
                if (time() <= $cacheData['expires']) {
                    $validEntries++;
                } else {
                    $expiredEntries++;
                }
            } else {
                $expiredEntries++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_entries' => $validEntries,
            'expired_entries' => $expiredEntries,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir
        ];
    }
    
    /**
     * Remember pattern - get from cache or execute callback and cache result
     */
    public function remember($key, $callback, $ttl = null) {
        $data = $this->get($key);
        
        if ($data !== null) {
            return $data;
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Cache database query results
     */
    public function cacheQuery($key, $query, $params = [], $types = '', $ttl = null) {
        return $this->remember($key, function() use ($query, $params, $types) {
            $db = Database::getInstance();
            return $db->select($query, $params, $types);
        }, $ttl);
    }
    
    /**
     * Cache single database query result
     */
    public function cacheQueryOne($key, $query, $params = [], $types = '', $ttl = null) {
        return $this->remember($key, function() use ($query, $params, $types) {
            $db = Database::getInstance();
            return $db->selectOne($query, $params, $types);
        }, $ttl);
    }
}

// Global cache instance
$cache = Cache::getInstance();

?>