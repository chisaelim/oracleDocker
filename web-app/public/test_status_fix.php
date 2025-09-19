<?php
// Test the status count fix
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    
    echo "Testing Status Count Fix\n";
    echo "=======================\n\n";
    
    // Test the OLD way (incorrect - counts detail lines)
    $sql_old = "SELECT 
                    NVL(i.INVOICE_STATUS, 'Unknown') as invoice_status,
                    COUNT(*) as status_count,
                    NVL(SUM(id.QTY * id.PRICE), 0) as status_revenue
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE >= SYSDATE - 30
                GROUP BY i.INVOICE_STATUS
                ORDER BY status_revenue DESC";
    
    $result = $db->query($sql_old);
    $old_data = $db->fetchAll($result);
    
    echo "OLD WAY (incorrect - counts detail lines):\n";
    foreach ($old_data as $row) {
        echo "- {$row['INVOICE_STATUS']}: {$row['STATUS_COUNT']} invoices, Revenue: \${$row['STATUS_REVENUE']}\n";
    }
    
    echo "\n";
    
    // Test the NEW way (correct - counts distinct invoices)
    $sql_new = "SELECT 
                    NVL(i.INVOICE_STATUS, 'Unknown') as invoice_status,
                    COUNT(DISTINCT i.INVOICENO) as status_count,
                    NVL(SUM(id.QTY * id.PRICE), 0) as status_revenue
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE >= SYSDATE - 30
                GROUP BY i.INVOICE_STATUS
                ORDER BY status_revenue DESC";
    
    $result = $db->query($sql_new);
    $new_data = $db->fetchAll($result);
    
    echo "NEW WAY (correct - counts distinct invoices):\n";
    foreach ($new_data as $row) {
        echo "- {$row['INVOICE_STATUS']}: {$row['STATUS_COUNT']} invoices, Revenue: \${$row['STATUS_REVENUE']}\n";
    }
    
    echo "\n";
    
    // Verify the total invoice count
    $total_sql = "SELECT COUNT(*) as total_invoices FROM INVOICES WHERE INVOICE_DATE >= SYSDATE - 30";
    $result = $db->query($total_sql);
    $total_data = $db->fetchOne($result);
    
    $old_total = array_sum(array_column($old_data, 'STATUS_COUNT'));
    $new_total = array_sum(array_column($new_data, 'STATUS_COUNT'));
    $actual_total = $total_data['TOTAL_INVOICES'];
    
    echo "VERIFICATION:\n";
    echo "- Actual total invoices in database: {$actual_total}\n";
    echo "- Old method total: {$old_total} (should be HIGHER due to counting detail lines)\n";
    echo "- New method total: {$new_total} (should match actual total)\n";
    echo "- Fix is " . ($new_total == $actual_total ? "WORKING ✓" : "NOT WORKING ✗") . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>