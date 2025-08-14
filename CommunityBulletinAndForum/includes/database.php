<?php

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->connection->connect_error) {
            error_log("Database connection failed: " . $this->connection->connect_error);
            die("Database connection failed. Please try again later.");
        }
        
        // Set charset to UTF-8
        $this->connection->set_charset("utf8");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prepared statement helper for SELECT queries
    public function select($query, $params = [], $types = '') {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->connection->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $data;
    }
    
    // Prepared statement helper for INSERT/UPDATE/DELETE queries
    public function execute($query, $params = [], $types = '') {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->connection->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $affected_rows = $stmt->affected_rows;
        $insert_id = $this->connection->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'affected_rows' => $affected_rows,
            'insert_id' => $insert_id
        ];
    }
    
    // Get single row
    public function selectOne($query, $params = [], $types = '') {
        $result = $this->select($query, $params, $types);
        return $result ? $result[0] ?? null : null;
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->connection->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->connection->rollback();
    }
    
    // Escape string (for cases where prepared statements aren't suitable)
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    // Get last error
    public function getError() {
        return $this->connection->error;
    }
    
    // Close connection
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Count records
    public function count($table, $where = '', $params = [], $types = '') {
        $query = "SELECT COUNT(*) as count FROM $table";
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        $result = $this->selectOne($query, $params, $types);
        return $result ? $result['count'] : 0;
    }
    
    // Check if record exists
    public function exists($table, $where, $params = [], $types = '') {
        return $this->count($table, $where, $params, $types) > 0;
    }
    
    // Insert or update (upsert)
    public function upsert($table, $data, $update_fields = []) {
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        $values = array_values($data);
        
        $query = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ($placeholders)";
        
        if (!empty($update_fields)) {
            $updates = [];
            foreach ($update_fields as $field) {
                $updates[] = "$field = VALUES($field)";
            }
            $query .= " ON DUPLICATE KEY UPDATE " . implode(',', $updates);
        }
        
        $types = str_repeat('s', count($values));
        return $this->execute($query, $values, $types);
    }
    
    // Get table info for debugging
    public function getTableInfo($table) {
        return $this->select("DESCRIBE $table");
    }
}

// Create global database instance for backward compatibility
$db = Database::getInstance();
$conn = $db->getConnection();

?>