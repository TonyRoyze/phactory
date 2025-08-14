<?php
/**
 * Cache Maintenance Script
 * 
 * This script should be run periodically (e.g., via cron job) to:
 * - Clean up expired cache entries
 * - Warm up frequently accessed caches
 * - Log cache statistics
 * 
 * Usage:
 * php scripts/cache-maintenance.php
 * 
 * Cron job example (run every 15 minutes):
 * /usr/bin/php /path/to/your/site/scripts/cache-maintenance.php
 **/

// Set script execution time limit
set_time_limit(300); // 5 minutes

// Include required files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure this script is run from command line only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

echo "Starting cache maintenance...\n";

try {
    $cache = Cache::getInstance();
    
    // Get initial cache stats
    $initial_stats = $cache->getStats();
    echo "Initial cache stats:\n";
    echo "- Total files: {$initial_stats['total_files']}\n";
    echo "- Valid entries: {$initial_stats['valid_entries']}\n";
    echo "- Expired entries: {$initial_stats['expired_entries']}\n";
    echo "- Total size: " . formatBytes($initial_stats['total_size']) . "\n\n";
    
    // Clean up expired entries
    echo "Cleaning up expired cache entries...\n";
    $cleaned = $cache->cleanup();
    echo "Cleaned $cleaned expired entries.\n\n";
    
    // Warm up critical caches
    echo "Warming up critical caches...\n";
    warmCache();
    echo "Cache warming completed.\n\n";
    
    // Get final cache stats
    $final_stats = $cache->getStats();
    echo "Final cache stats:\n";
    echo "- Total files: {$final_stats['total_files']}\n";
    echo "- Valid entries: {$final_stats['valid_entries']}\n";
    echo "- Expired entries: {$final_stats['expired_entries']}\n";
    echo "- Total size: " . formatBytes($final_stats['total_size']) . "\n\n";
    
    // Log maintenance activity
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => 'cache_maintenance',
        'cleaned_entries' => $cleaned,
        'initial_stats' => $initial_stats,
        'final_stats' => $final_stats
    ];
    
    $log_file = __DIR__ . '/../logs/cache-maintenance.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    
    echo "Cache maintenance completed successfully.\n";
    echo "Log entry written to: $log_file\n";
    
} catch (Exception $e) {
    echo "Error during cache maintenance: " . $e->getMessage() . "\n";
    
    // Log error
    $error_log = __DIR__ . '/../logs/cache-errors.log';
    $error_dir = dirname($error_log);
    
    if (!is_dir($error_dir)) {
        mkdir($error_dir, 0755, true);
    }
    
    $error_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ];
    
    file_put_contents($error_log, json_encode($error_entry) . "\n", FILE_APPEND | LOCK_EX);
    
    exit(1);
}

/**
 * Format bytes into human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>