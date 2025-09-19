<?php
// Simple test page
echo "Testing page load...";

try {
    require_once 'config/database.php';
    echo "Database config loaded...";
    
    $db = Database::getInstance();
    echo "Database instance created...";
    
    $connection = $db->getConnection();
    echo "Database connection obtained...";
    
    if ($connection) {
        echo "Database connection successful!";
    } else {
        echo "Database connection failed!";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>