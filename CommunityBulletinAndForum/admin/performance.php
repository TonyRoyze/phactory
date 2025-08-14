<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Initialize session
initializeSession();

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Enable performance tracking for this page
QueryPerformanceTracker::enable();
PagePerformanceTracker::start();

// Get cache statistics
$cache = Cache::getInstance();
$cache_stats = $cache->getStats();

// Get database performance recommendations
$db_recommendations = getDatabasePerformanceRecommendations();

// Get recent cache maintenance logs
$cache_log_file = __DIR__ . '/../logs/cache-maintenance.log';
$recent_maintenance = [];
if (file_exists($cache_log_file)) {
    $lines = array_slice(file($cache_log_file, FILE_IGNORE_NEW_LINES), -10);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data) {
            $recent_maintenance[] = $data;
        }
    }
    $recent_maintenance = array_reverse($recent_maintenance);
}

// Get slow query logs
$slow_query_file = __DIR__ . '/../logs/slow-queries.log';
$slow_queries = [];
if (file_exists($slow_query_file)) {
    $lines = array_slice(file($slow_query_file, FILE_IGNORE_NEW_LINES), -20);
    foreach ($lines as $line) {
        $data = json_decode($line, true);
        if ($data) {
            $slow_queries[] = $data;
        }
    }
    $slow_queries = array_reverse($slow_queries);
}

$page_title = 'Performance Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - ' . SITE_NAME); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/theme.css">
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .performance-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dashboard-card h3 {
            margin-top: 0;
            color: var(--primary-blue);
            border-bottom: 2px solid var(--light-blue);
            padding-bottom: 10px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-value {
            font-weight: bold;
            color: var(--primary-blue);
        }
        
        .recommendation {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin: 5px 0;
        }
        
        .log-entry {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 10px;
            margin: 5px 0;
            font-size: 0.9em;
        }
        
        .slow-query {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px;
            margin: 5px 0;
            font-size: 0.9em;
        }
        
        .cache-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.9em;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="performance-dashboard">
        <h1><i class="fas fa-tachometer-alt"></i> Performance Dashboard</h1>
        
        <div class="dashboard-grid">
            <!-- Cache Statistics -->
            <div class="dashboard-card">
                <h3><i class="fas fa-database"></i> Cache Statistics</h3>
                <div class="stat-item">
                    <span>Total Cache Files:</span>
                    <span class="stat-value"><?php echo number_format($cache_stats['total_files']); ?></span>
                </div>
                <div class="stat-item">
                    <span>Valid Entries:</span>
                    <span class="stat-value"><?php echo number_format($cache_stats['valid_entries']); ?></span>
                </div>
                <div class="stat-item">
                    <span>Expired Entries:</span>
                    <span class="stat-value"><?php echo number_format($cache_stats['expired_entries']); ?></span>
                </div>
                <div class="stat-item">
                    <span>Total Size:</span>
                    <span class="stat-value"><?php echo formatBytes($cache_stats['total_size']); ?></span>
                </div>
                
                <div class="cache-actions">
                    <button class="btn btn-primary btn-small" onclick="performCacheAction('cleanup')">
                        <i class="fas fa-broom"></i> Cleanup
                    </button>
                    <button class="btn btn-warning btn-small" onclick="performCacheAction('clear')">
                        <i class="fas fa-trash"></i> Clear All
                    </button>
                    <button class="btn btn-success btn-small" onclick="performCacheAction('warm')">
                        <i class="fas fa-fire"></i> Warm Cache
                    </button>
                    <button class="btn btn-info btn-small" onclick="performCacheAction('maintenance')">
                        <i class="fas fa-tools"></i> Maintenance
                    </button>
                </div>
            </div>
            
            <!-- Database Recommendations -->
            <div class="dashboard-card">
                <h3><i class="fas fa-lightbulb"></i> Performance Recommendations</h3>
                <?php if (empty($db_recommendations)): ?>
                    <p class="text-success">No performance issues detected!</p>
                <?php else: ?>
                    <?php foreach ($db_recommendations as $recommendation): ?>
                        <div class="recommendation">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($recommendation); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- System Information -->
            <div class="dashboard-card">
                <h3><i class="fas fa-server"></i> System Information</h3>
                <div class="stat-item">
                    <span>PHP Version:</span>
                    <span class="stat-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="stat-item">
                    <span>Memory Limit:</span>
                    <span class="stat-value"><?php echo ini_get('memory_limit'); ?></span>
                </div>
                <div class="stat-item">
                    <span>Max Execution Time:</span>
                    <span class="stat-value"><?php echo ini_get('max_execution_time'); ?>s</span>
                </div>
                <div class="stat-item">
                    <span>Current Memory Usage:</span>
                    <span class="stat-value"><?php echo formatBytes(memory_get_usage()); ?></span>
                </div>
                <div class="stat-item">
                    <span>Peak Memory Usage:</span>
                    <span class="stat-value"><?php echo formatBytes(memory_get_peak_usage()); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Recent Cache Maintenance -->
        <div class="dashboard-card">
            <h3><i class="fas fa-history"></i> Recent Cache Maintenance</h3>
            <?php if (empty($recent_maintenance)): ?>
                <p>No recent maintenance logs found.</p>
            <?php else: ?>
                <?php foreach ($recent_maintenance as $entry): ?>
                    <div class="log-entry">
                        <strong><?php echo htmlspecialchars($entry['timestamp']); ?></strong> - 
                        Cleaned <?php echo $entry['cleaned_entries']; ?> entries
                        (<?php echo $entry['final_stats']['total_files']; ?> files, 
                        <?php echo formatBytes($entry['final_stats']['total_size']); ?>)
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Slow Queries -->
        <?php if (!empty($slow_queries)): ?>
        <div class="dashboard-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Recent Slow Queries</h3>
            <?php foreach (array_slice($slow_queries, 0, 10) as $query): ?>
                <div class="slow-query">
                    <strong><?php echo htmlspecialchars($query['timestamp']); ?></strong> - 
                    Duration: <?php echo number_format($query['duration'] * 1000, 2); ?>ms<br>
                    <code><?php echo htmlspecialchars(substr($query['query'], 0, 100)) . (strlen($query['query']) > 100 ? '...' : ''); ?></code>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        async function performCacheAction(action) {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            
            // Show loading state
            button.classList.add('loading');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
                
                const response = await fetch('../api/cache-management.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload(); // Refresh to show updated stats
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            } finally {
                // Restore button state
                button.classList.remove('loading');
                button.innerHTML = originalText;
            }
        }
    </script>
</body>
</html>

<?php
// Helper function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// End performance tracking
$performance_stats = PagePerformanceTracker::end();

// Log page performance if it's slow
if ($performance_stats['execution_time'] > 1.0) { // More than 1 second
    error_log("Slow page load: performance.php took {$performance_stats['execution_time']}s");
}
?>