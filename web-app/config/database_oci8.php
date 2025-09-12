<?php
// Database Configuration for Oracle Database using OCI8
class DatabaseOCI8 {
    // Oracle Database connection parameters
    private const DB_HOST = 'oracle-db';  // Docker service name
    private const DB_PORT = '1521';
    private const DB_SERVICE = 'XEPDB1';  // Pluggable database name
    private const DB_USERNAME = 'appuser';
    private const DB_PASSWORD = 'appuser123';
    
    private static $connection = null;
    
    /**
     * Get database connection using OCI8
     * @return resource|null
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // Check if OCI8 extension is available
                if (!extension_loaded('oci8')) {
                    throw new Exception("Oracle OCI8 extension is not installed. Please install PHP Oracle extensions.");
                }
                
                // Oracle connection string for OCI8
                $connection_string = sprintf(
                    "//%s:%s/%s",
                    self::DB_HOST,
                    self::DB_PORT,
                    self::DB_SERVICE
                );
                
                // Create OCI8 connection
                self::$connection = oci_connect(
                    self::DB_USERNAME,
                    self::DB_PASSWORD,
                    $connection_string,
                    'AL32UTF8'
                );
                
                if (!self::$connection) {
                    $error = oci_error();
                    throw new Exception("Oracle connection failed: " . $error['message']);
                }
                
                // Set session parameters
                $stmt = oci_parse(self::$connection, "ALTER SESSION SET TIME_ZONE = 'UTC'");
                oci_execute($stmt);
                $stmt = oci_parse(self::$connection, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
                oci_execute($stmt);
                
            } catch (Exception $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check your configuration.");
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Close database connection
     */
    public static function closeConnection() {
        if (self::$connection) {
            oci_close(self::$connection);
            self::$connection = null;
        }
    }
    
    /**
     * Execute a query and return results as associative array
     * @param string $sql
     * @param array $params
     * @return array
     */
    public static function query($sql, $params = []) {
        $connection = self::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        if (!$stmt) {
            $error = oci_error($connection);
            throw new Exception("SQL parse error: " . $error['message']);
        }
        
        // Bind parameters
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }
        
        $result = oci_execute($stmt);
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception("SQL execution error: " . $error['message']);
        }
        
        $rows = [];
        while (($row = oci_fetch_assoc($stmt)) !== false) {
            $rows[] = $row;
        }
        
        oci_free_statement($stmt);
        return $rows;
    }
    
    /**
     * Execute a single query and return first result
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public static function queryOne($sql, $params = []) {
        $results = self::query($sql, $params);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Test database connection
     * @return array
     */
    public static function testConnection() {
        try {
            $connection = self::getConnection();
            $result = self::queryOne("SELECT 'Connection successful' as status, SYSDATE as current_time FROM DUAL");
            return [
                'success' => true,
                'message' => $result['STATUS'],
                'timestamp' => $result['CURRENT_TIME']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get database information
     * @return array
     */
    public static function getDatabaseInfo() {
        try {
            $version = self::queryOne("
                SELECT BANNER as version
                FROM V\$VERSION 
                WHERE BANNER LIKE 'Oracle%'
            ");
            
            $session = self::queryOne("
                SELECT 
                    SYS_CONTEXT('USERENV', 'DB_NAME') as db_name,
                    SYS_CONTEXT('USERENV', 'CURRENT_USER') as current_user,
                    SYS_CONTEXT('USERENV', 'SESSION_USER') as session_user
                FROM DUAL
            ");
            
            return array_merge($version ?: [], $session ?: []);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>