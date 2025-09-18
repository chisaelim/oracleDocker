<?php
/**
 * Database Connection Class
 */

require_once __DIR__ . '/config.php';

class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Connection string for Oracle - simple format
            $connection_string = DB_HOST . ":" . DB_PORT . "/" . DB_SERVICE;
            
            $this->connection = oci_connect(DB_USERNAME, DB_PASSWORD, $connection_string, 'UTF8');
            
            if (!$this->connection) {
                $error = oci_error();
                throw new Exception("Connection failed: " . $error['message']);
            }
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = oci_parse($this->connection, $sql);
            
            if (!$stmt) {
                $error = oci_error($this->connection);
                throw new Exception("Parse failed: " . $error['message']);
            }
            
            // Bind parameters if provided
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    oci_bind_by_name($stmt, $key, $params[$key]);
                }
            }
            
            $result = oci_execute($stmt);
            
            if (!$result) {
                $error = oci_error($stmt);
                throw new Exception("Execute failed: " . $error['message']);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function fetchAll($stmt) {
        $results = [];
        while (($row = oci_fetch_assoc($stmt)) !== false) {
            $results[] = $row;
        }
        oci_free_statement($stmt);
        return $results;
    }
    
    public function fetchOne($stmt) {
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row;
    }
    
    public function commit() {
        return oci_commit($this->connection);
    }
    
    public function rollback() {
        return oci_rollback($this->connection);
    }
    
    public function close() {
        if ($this->connection) {
            oci_close($this->connection);
            $this->connection = null;
        }
    }
    
    public function isConnected() {
        return $this->connection !== null;
    }
    
    public function getLastError() {
        return oci_error($this->connection);
    }
    
    public function __destruct() {
        $this->close();
    }
}
?>