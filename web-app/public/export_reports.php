<?php
// export_reports.php - Handle PDF and Excel export of reports
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/utils.php';

// Check if export request is valid
if (!isset($_POST['export_type']) || !isset($_POST['report_type'])) {
    die('Invalid export request');
}

$export_type = $_POST['export_type'];
$report_type = $_POST['report_type'];
$start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_POST['end_date'] ?? date('Y-m-d');
$active_tab = $_POST['active_tab'] ?? 'sales';

try {
    $db = Database::getInstance();
    
    // Get data based on report type
    switch ($active_tab) {
        case 'sales':
            $data = getSalesDataForExport($db, $start_date, $end_date);
            break;
        case 'inventory':
            $data = getInventoryDataForExport($db);
            break;
        case 'clients':
            $data = getClientDataForExport($db, $start_date, $end_date);
            break;
        case 'employees':
            $data = getEmployeeDataForExport($db, $start_date, $end_date);
            break;
        default:
            $data = getSalesDataForExport($db, $start_date, $end_date);
    }
    
    if ($export_type === 'excel') {
        exportToExcel($data, $report_type, $start_date, $end_date);
    } else {
        exportToPDF($data, $report_type, $start_date, $end_date);
    }
    
} catch (Exception $e) {
    die('Export error: ' . $e->getMessage());
}

function getSalesDataForExport($db, $start_date, $end_date) {
    $sql = "SELECT 
                TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD') as invoice_date,
                i.INVOICENO as invoice_no,
                c.CLIENTNAME as client_name,
                e.EMPLOYEENAME as employee_name,
                i.INVOICE_STATUS as status,
                NVL(c.DISCOUNT, 0) as client_discount,
                NVL(SUM(id.QTY * id.PRICE), 0) as subtotal,
                NVL(SUM(((id.QTY * id.PRICE) * NVL(c.DISCOUNT, 0)) / 100), 0) as discount_amount,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) as amount,
                NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)) - SUM(id.QTY * p.COST_PRICE), 0) as profit
            FROM INVOICES i
            LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
            LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
            LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
            LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
            WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                  AND TO_DATE(:end_date, 'YYYY-MM-DD')
            GROUP BY i.INVOICENO, i.INVOICE_DATE, c.CLIENTNAME, e.EMPLOYEENAME, i.INVOICE_STATUS, c.DISCOUNT
            ORDER BY i.INVOICE_DATE DESC";
    
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    $stmt = $db->query($sql, $params);
    return $db->fetchAll($stmt);
}

function getInventoryDataForExport($db) {
    $sql = "SELECT 
                p.PRODUCTNAME as product_name,
                pt.PRODUCTTYPE_NAME as category,
                p.QTY_ON_HAND as stock_level,
                p.REORDER_LEVEL as reorder_level,
                NVL(SUM(id.QTY), 0) as units_sold,
                NVL(SUM(id.QTY * id.PRICE), 0) as subtotal,
                NVL(SUM(((id.QTY * id.PRICE) * NVL(c.DISCOUNT, 0)) / 100), 0) as discount_amount,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) as revenue,
                NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)) - SUM(id.QTY * p.COST_PRICE), 0) as profit
            FROM Products p
            LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
            LEFT JOIN INVOICE_DETAILS id ON p.PRODUCT_NO = id.PRODUCT_NO
            LEFT JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
            LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
            GROUP BY p.PRODUCTNAME, pt.PRODUCTTYPE_NAME, p.QTY_ON_HAND, p.REORDER_LEVEL
            ORDER BY revenue DESC";
    
    $stmt = $db->query($sql);
    return $db->fetchAll($stmt);
}

function getClientDataForExport($db, $start_date, $end_date) {
    $sql = "SELECT 
                c.CLIENTNAME as client_name,
                ct.TYPE_NAME as client_type,
                NVL(c.DISCOUNT, 0) as client_discount,
                COUNT(DISTINCT i.INVOICENO) as total_orders,
                NVL(SUM(id.QTY * id.PRICE), 0) as total_subtotal,
                NVL(SUM(((id.QTY * id.PRICE) * NVL(c.DISCOUNT, 0)) / 100), 0) as total_discount_given,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) as total_revenue,
                NVL(SUM(id.QTY * p.COST_PRICE), 0) as total_cost,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)) - SUM(id.QTY * p.COST_PRICE), 0) as total_profit,
                TO_CHAR(MAX(i.INVOICE_DATE), 'YYYY-MM-DD') as last_order,
                CASE 
                    WHEN MAX(i.INVOICE_DATE) >= SYSDATE - 30 THEN 'Active'
                    ELSE 'Inactive'
                END as status
            FROM Clients c
            LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
            LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO
            LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
            LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
            WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                  AND TO_DATE(:end_date, 'YYYY-MM-DD')
            GROUP BY c.CLIENTNAME, ct.TYPE_NAME, c.DISCOUNT
            ORDER BY total_revenue DESC";
    
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    $stmt = $db->query($sql, $params);
    return $db->fetchAll($stmt);
}

function getEmployeeDataForExport($db, $start_date, $end_date) {
    $sql = "SELECT 
                e.EMPLOYEENAME as employee_name,
                j.JOB_TITLE as job_title,
                COUNT(DISTINCT i.INVOICENO) as total_sales,
                NVL(SUM(id.QTY * id.PRICE), 0) as gross_revenue,
                NVL(SUM(((id.QTY * id.PRICE) * NVL(c.DISCOUNT, 0)) / 100), 0) as discount_given,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) as revenue,
                NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)) - SUM(id.QTY * p.COST_PRICE), 0) as profit,
                CASE 
                    WHEN COUNT(DISTINCT i.INVOICENO) > 0 
                    THEN ROUND(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)) / COUNT(DISTINCT i.INVOICENO), 2)
                    ELSE 0 
                END as avg_order_value,
                CASE 
                    WHEN NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) > 10000 THEN 'Excellent'
                    WHEN NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) > 5000 THEN 'Good'
                    WHEN NVL(SUM(id.QTY * id.PRICE * (1 - NVL(c.DISCOUNT, 0) / 100)), 0) > 1000 THEN 'Average'
                    ELSE 'Poor'
                END as performance
            FROM Employees e
            LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
            LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID
            LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
            LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
            LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
            WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                  AND TO_DATE(:end_date, 'YYYY-MM-DD')
            GROUP BY e.EMPLOYEENAME, j.JOB_TITLE
            ORDER BY revenue DESC";
    
    $params = [':start_date' => $start_date, ':end_date' => $end_date];
    $stmt = $db->query($sql, $params);
    return $db->fetchAll($stmt);
}

function exportToExcel($data, $report_type, $start_date, $end_date) {
    // Set headers for proper Excel CSV download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $report_type) . '_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8 Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write report header information
    fputcsv($output, [$report_type]);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, ['Date Range: ' . $start_date . ' to ' . $end_date]);
    fputcsv($output, []); // Empty row for spacing
    
    if (!empty($data)) {
        // Write column headers
        $firstRow = reset($data);
        $headers = array_map(function($header) {
            return ucwords(str_replace('_', ' ', $header));
        }, array_keys($firstRow));
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            // Convert all values to strings and handle nulls
            $cleanRow = array_map(function($cell) {
                if ($cell === null) {
                    return '';
                }
                // Format numbers properly for Excel
                if (is_numeric($cell)) {
                    return number_format((float)$cell, 2, '.', '');
                }
                return (string)$cell;
            }, $row);
            fputcsv($output, $cleanRow);
        }
    } else {
        fputcsv($output, ['No data found for the selected criteria']);
    }
    
    fclose($output);
}

function exportToPDF($data, $report_type, $start_date, $end_date) {
    // For PDF, we'll create an HTML page that can be printed as PDF
    // In a production environment, you might want to use a library like TCPDF or FPDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title><?php echo htmlspecialchars($report_type); ?> - PDF Export</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .info { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .print-button { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 20px 0; }
            @media print { .print-button { display: none; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php echo htmlspecialchars($report_type); ?></h1>
            <button class="print-button" onclick="window.print()">Print / Save as PDF</button>
        </div>
        
        <div class="info">
            <p><strong>Generated on:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Date Range:</strong> <?php echo htmlspecialchars($start_date) . ' to ' . htmlspecialchars($end_date); ?></p>
            <p><strong>Total Records:</strong> <?php echo count($data); ?></p>
        </div>
        
        <?php if (!empty($data)): ?>
        <table>
            <thead>
                <tr>
                    <?php 
                    $firstRow = reset($data);
                    foreach (array_keys($firstRow) as $header): 
                    ?>
                        <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $header))); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?php echo htmlspecialchars($cell ?? ''); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No data found for the selected criteria.</p>
        <?php endif; ?>
        
        <script>
            // Auto-print dialog on load for PDF export
            window.onload = function() {
                document.querySelector('.print-button').focus();
            };
        </script>
    </body>
    </html>
    <?php
}
?>