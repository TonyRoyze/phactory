<?php

/**
 * Session Management Helper
 * This file provides additional session management utilities
 */

// Config is already included by the main file

/**
 * Initialize session with security checks
 */
function initSecureSession() {
    // Check if session is already started
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Validate session security
        validateSessionSecurity();
        return true;
    }
    
    return false;
}

/**
 * Validate session security
 */
function validateSessionSecurity() {
    // Check for session hijacking
    if (isset($_SESSION['HTTP_USER_AGENT'])) {
        if ($_SESSION['HTTP_USER_AGENT'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            session_destroy();
            session_start();
            return false;
        }
    } else {
        $_SESSION['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
            session_destroy();
            session_start();
            return false;
        }
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Regenerate session ID periodically for security
 */
function regenerateSessionId() {
    // Regenerate session ID every 30 minutes
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

/**
 * Clean expired sessions (call this periodically)
 */
function cleanExpiredSessions() {
    // This would typically be handled by PHP's garbage collection
    // but we can add custom logic here if needed
    if (rand(1, 100) === 1) { // 1% chance to run cleanup
        // Custom session cleanup logic could go here
        // For now, rely on PHP's built-in garbage collection
    }
}

// Initialize session security on include
initSecureSession();
regenerateSessionId();
cleanExpiredSessions();

?>