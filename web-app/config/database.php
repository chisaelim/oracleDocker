<?php
// Database Configuration for Oracle Database
class DatabaseConfig {
    // Oracle Database connection parameters
    private const DB_HOST = 'oracle-db';  // Docker service name
    private const DB_PORT = '1521';
    private const DB_SERVICE = 'XEPDB1';  // Pluggable database name
    private const DB_USERNAME = 'appuser';
    private const DB_PASSWORD = 'appuser123';
    
    private static $connection = null;
    
    /**
     * Get database connection using PDO
     * @return PDO|null
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                // Check if Oracle PDO extension is available
                if (!extension_loaded('pdo_oci')) {
                    throw new Exception("Oracle PDO extension (pdo_oci) is not installed. Please install PHP Oracle extensions.");
                }
                
                // Oracle PDO connection string
                $dsn = sprintf(
                    "oci:dbname=//%s:%s/%s;charset=UTF8",
                    self::DB_HOST,
                    self::DB_PORT,
                    self::DB_SERVICE
                );
                
                // PDO options for better error handling and performance
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                ];
                
                self::$connection = new PDO($dsn, self::DB_USERNAME, self::DB_PASSWORD, $options);
                
                // Set session timezone and NLS parameters
                self::$connection->exec("ALTER SESSION SET TIME_ZONE = 'UTC'");
                self::$connection->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check your configuration.");
            } catch (Exception $e) {
                error_log("Database extension error: " . $e->getMessage());
                throw new Exception($e->getMessage());
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Close database connection
     */
    public static function closeConnection() {
        self::$connection = null;
    }
    
    /**
     * Test database connection
     * @return array
     */
    public static function testConnection() {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SELECT 'Connection successful' as status, SYSDATE as current_time FROM DUAL");
            $result = $stmt->fetch();
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
            $pdo = self::getConnection();
            $stmt = $pdo->query("
                SELECT 
                    BANNER as version,
                    (SELECT SYS_CONTEXT('USERENV', 'DB_NAME') FROM DUAL) as db_name,
                    (SELECT SYS_CONTEXT('USERENV', 'CURRENT_USER') FROM DUAL) as current_user,
                    (SELECT SYS_CONTEXT('USERENV', 'SESSION_USER') FROM DUAL) as session_user
                FROM V\$VERSION 
                WHERE BANNER LIKE 'Oracle%'
            ");
            return $stmt->fetch();
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>