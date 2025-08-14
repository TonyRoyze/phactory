<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

// Initialize session
initializeSession();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is admin (for security)
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    $cache = Cache::getInstance();
    
    switch ($action) {
        case 'stats':
            $stats = $cache->getStats();
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            break;
            
        case 'cleanup':
            $cleaned = $cache->cleanup();
            echo json_encode([
                'success' => true,
                'message' => "Cleaned $cleaned expired cache entries",
                'cleaned_count' => $cleaned
            ]);
            break;
            
        case 'clear':
            $cleared = $cache->clear();
            echo json_encode([
                'success' => true,
                'message' => "Cleared $cleared cache entries",
                'cleared_count' => $cleared
            ]);
            break;
            
        case 'warm':
            warmCache();
            echo json_encode([
                'success' => true,
                'message' => 'Cache warmed successfully'
            ]);
            break;
            
        case 'maintenance':
            $cleaned = performCacheMaintenance();
            echo json_encode([
                'success' => true,
                'message' => "Cache maintenance completed. Cleaned $cleaned expired entries",
                'cleaned_count' => $cleaned
            ]);
            break;
            
        case 'invalidate':
            $type = $_POST['type'] ?? '';
            $id = $_POST['id'] ?? null;
            
            if (empty($type)) {
                throw new Exception('Type parameter is required for invalidation');
            }
            
            invalidateRelatedCaches($type, $id);
            echo json_encode([
                'success' => true,
                'message' => "Invalidated caches for type: $type"
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Cache operation failed',
        'message' => $e->getMessage()
    ]);
}
?>