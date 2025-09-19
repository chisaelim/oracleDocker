<?php
// Simple AJAX test
require_once 'config/config.php';
require_once 'config/database.php';

try {
    echo "Testing database connection...\n";
    $db = Database::getInstance();
    
    // Test basic query
    $result = $db->query("SELECT COUNT(*) as total FROM INVOICES");
    $data = $db->fetchOne($result);
    
    echo "Total invoices in database: " . $data['TOTAL'] . "\n";
    
    // Test a more complex query like in getSalesReportData
    $sql = "SELECT 
                NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                COUNT(DISTINCT i.INVOICENO) as total_invoices,
                COUNT(DISTINCT i.CLIENT_NO) as active_clients
            FROM INVOICES i
            LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
            WHERE i.INVOICE_DATE >= SYSDATE - 30";
    
    $result = $db->query($sql);
    $summary = $db->fetchOne($result);
    
    echo "Revenue (last 30 days): $" . $summary['TOTAL_REVENUE'] . "\n";
    echo "Total invoices (last 30 days): " . $summary['TOTAL_INVOICES'] . "\n";
    echo "Active clients (last 30 days): " . $summary['ACTIVE_CLIENTS'] . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>