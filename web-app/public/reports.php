<?php
// reports.php - Business Reports Dashboard with Date Range Filtering
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/utils.php';

// PHP Functions for AJAX endpoints
function getSalesReportData() {
    try {
        $db = Database::getInstance();
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // Enhanced Summary data with profit analysis
        $sql = "SELECT 
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as total_cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as total_profit,
                    CASE 
                        WHEN SUM(id.QTY * id.PRICE) > 0 
                        THEN ROUND(((SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE)) / SUM(id.QTY * id.PRICE)) * 100, 2)
                        ELSE 0 
                    END as profit_margin_percent,
                    COUNT(DISTINCT i.INVOICENO) as total_invoices,
                    COUNT(DISTINCT i.CLIENT_NO) as active_clients,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_order_value,
                    NVL(SUM(id.QTY), 0) as total_items_sold
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $summary = $db->fetchOne($stmt);
        
        // Enhanced chart data - daily sales with profit
        $sql = "SELECT 
                    TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD') as sale_date,
                    NVL(SUM(id.QTY * id.PRICE), 0) as daily_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as daily_cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as daily_profit,
                    COUNT(DISTINCT i.INVOICENO) as daily_orders
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD')
                ORDER BY sale_date";
        
        $stmt = $db->query($sql, $params);
        $chart_raw = $db->fetchAll($stmt);
        
        $chart_data = [
            'labels' => array_column($chart_raw, 'SALE_DATE'),
            'revenue' => array_map('floatval', array_column($chart_raw, 'DAILY_REVENUE')),
            'profit' => array_map('floatval', array_column($chart_raw, 'DAILY_PROFIT')),
            'orders' => array_map('intval', array_column($chart_raw, 'DAILY_ORDERS'))
        ];
        
        // Enhanced status distribution with amounts
        $sql = "SELECT 
                    NVL(i.INVOICE_STATUS, 'Unknown') as invoice_status,
                    COUNT(DISTINCT i.INVOICENO) as status_count,
                    NVL(SUM(id.QTY * id.PRICE), 0) as status_revenue
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY i.INVOICE_STATUS
                ORDER BY status_revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $status_raw = $db->fetchAll($stmt);
        
        $status_data = [
            'labels' => array_column($status_raw, 'INVOICE_STATUS'),
            'counts' => array_map('intval', array_column($status_raw, 'STATUS_COUNT')),
            'revenues' => array_map('floatval', array_column($status_raw, 'STATUS_REVENUE'))
        ];
        
        // Top products by revenue
        $sql = "SELECT 
                    p.PRODUCTNAME as product_name,
                    pt.PRODUCTTYPE_NAME as category,
                    NVL(SUM(id.QTY), 0) as units_sold,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as profit
                FROM INVOICE_DETAILS id
                JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY p.PRODUCTNAME, pt.PRODUCTTYPE_NAME
                ORDER BY revenue DESC
                FETCH FIRST 10 ROWS ONLY";
        
        $stmt = $db->query($sql, $params);
        $top_products = $db->fetchAll($stmt);
        
        // Detailed data with profit information
        $sql = "SELECT 
                    TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD') as invoice_date,
                    i.INVOICENO as invoice_no,
                    c.CLIENTNAME as client_name,
                    ct.TYPE_NAME as client_type,
                    e.EMPLOYEENAME as employee_name,
                    j.JOB_TITLE as employee_job,
                    i.INVOICE_STATUS as status,
                    NVL(c.DISCOUNT, 0) as client_discount,
                    NVL(SUM(id.QTY * id.PRICE), 0) as amount,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as profit,
                    COUNT(id.PRODUCT_NO) as items_count
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY i.INVOICENO, i.INVOICE_DATE, c.CLIENTNAME, ct.TYPE_NAME, 
                         e.EMPLOYEENAME, j.JOB_TITLE, i.INVOICE_STATUS, c.DISCOUNT
                ORDER BY i.INVOICE_DATE DESC";
        
        $stmt = $db->query($sql, $params);
        $detailed_data = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'chart_data' => $chart_data,
            'status_data' => $status_data,
            'top_products' => $top_products,
            'detailed_data' => $detailed_data
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getInventoryReportData() {
    try {
        $db = Database::getInstance();
        
        // Enhanced inventory summary
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    SUM(p.QTY_ON_HAND) as total_stock_units,
                    SUM(p.QTY_ON_HAND * p.COST_PRICE) as total_stock_value,
                    SUM(p.QTY_ON_HAND * p.SELL_PRICE) as total_retail_value,
                    COUNT(CASE WHEN p.QTY_ON_HAND <= p.REORDER_LEVEL THEN 1 END) as low_stock_items,
                    COUNT(CASE WHEN p.QTY_ON_HAND = 0 THEN 1 END) as out_of_stock_items,
                    AVG(p.PROFIT_PERCENT) as avg_profit_margin
                FROM Products p";
        
        $stmt = $db->query($sql);
        $inventory_summary = $db->fetchOne($stmt);
        
        // Stock levels by category for chart
        $sql = "SELECT 
                    pt.PRODUCTTYPE_NAME as category,
                    COUNT(p.PRODUCT_NO) as product_count,
                    SUM(p.QTY_ON_HAND) as total_stock,
                    SUM(p.QTY_ON_HAND * p.COST_PRICE) as category_value,
                    AVG(p.PROFIT_PERCENT) as avg_margin
                FROM Products p
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                GROUP BY pt.PRODUCTTYPE_NAME
                ORDER BY total_stock DESC";
        
        $stmt = $db->query($sql);
        $category_stock = $db->fetchAll($stmt);
        
        $stock_data = [
            'labels' => array_column($category_stock, 'CATEGORY'),
            'stock_levels' => array_map('intval', array_column($category_stock, 'TOTAL_STOCK')),
            'values' => array_map('floatval', array_column($category_stock, 'CATEGORY_VALUE')),
            'margins' => array_map('floatval', array_column($category_stock, 'AVG_MARGIN'))
        ];
        
        // Low stock items with enhanced information
        $sql = "SELECT 
                    p.PRODUCTNAME as product_name,
                    pt.PRODUCTTYPE_NAME as category,
                    p.QTY_ON_HAND as stock_level,
                    p.REORDER_LEVEL as reorder_level,
                    p.SELL_PRICE as unit_price,
                    p.COST_PRICE as unit_cost,
                    (p.REORDER_LEVEL - p.QTY_ON_HAND) as units_needed,
                    (p.REORDER_LEVEL - p.QTY_ON_HAND) * p.COST_PRICE as reorder_cost,
                    CASE 
                        WHEN p.QTY_ON_HAND = 0 THEN 'Out of Stock'
                        WHEN p.QTY_ON_HAND <= p.REORDER_LEVEL * 0.5 THEN 'Critical'
                        ELSE 'Low Stock'
                    END as urgency_level
                FROM Products p
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                WHERE p.QTY_ON_HAND <= p.REORDER_LEVEL
                ORDER BY urgency_level DESC, p.QTY_ON_HAND ASC";
        
        $stmt = $db->query($sql);
        $low_stock = $db->fetchAll($stmt);
        
        // Product performance with turnover analysis
        $sql = "SELECT 
                    p.PRODUCTNAME as product_name,
                    pt.PRODUCTTYPE_NAME as category,
                    p.QTY_ON_HAND as stock_level,
                    p.REORDER_LEVEL as reorder_level,
                    p.SELL_PRICE as unit_price,
                    p.COST_PRICE as unit_cost,
                    p.PROFIT_PERCENT as profit_margin,
                    NVL(SUM(id.QTY), 0) as units_sold,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost_of_goods_sold,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as gross_profit,
                    CASE 
                        WHEN p.QTY_ON_HAND > 0 AND SUM(id.QTY) > 0 
                        THEN ROUND(SUM(id.QTY) / p.QTY_ON_HAND, 2)
                        ELSE 0 
                    END as turnover_ratio,
                    CASE 
                        WHEN SUM(id.QTY) > 0 THEN 'Active'
                        WHEN p.QTY_ON_HAND > 0 THEN 'Slow Moving'
                        ELSE 'Dead Stock'
                    END as movement_status
                FROM Products p
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                LEFT JOIN INVOICE_DETAILS id ON p.PRODUCT_NO = id.PRODUCT_NO
                LEFT JOIN INVOICES i ON id.INVOICENO = i.INVOICENO 
                    AND i.INVOICE_DATE >= SYSDATE - 365
                GROUP BY p.PRODUCTNAME, pt.PRODUCTTYPE_NAME, p.QTY_ON_HAND, 
                         p.REORDER_LEVEL, p.SELL_PRICE, p.COST_PRICE, p.PROFIT_PERCENT
                ORDER BY revenue DESC";
        
        $stmt = $db->query($sql);
        $product_performance = $db->fetchAll($stmt);
        
        // Top performing products for chart
        $sql = "SELECT 
                    p.PRODUCTNAME as product_name,
                    NVL(SUM(id.QTY), 0) as units_sold,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue
                FROM Products p
                LEFT JOIN INVOICE_DETAILS id ON p.PRODUCT_NO = id.PRODUCT_NO
                LEFT JOIN INVOICES i ON id.INVOICENO = i.INVOICENO 
                    AND i.INVOICE_DATE >= SYSDATE - 90
                GROUP BY p.PRODUCTNAME
                HAVING SUM(id.QTY) > 0
                ORDER BY revenue DESC
                FETCH FIRST 10 ROWS ONLY";
        
        $stmt = $db->query($sql);
        $top_products_raw = $db->fetchAll($stmt);
        
        $top_products = [
            'labels' => array_column($top_products_raw, 'PRODUCT_NAME'),
            'units' => array_map('intval', array_column($top_products_raw, 'UNITS_SOLD')),
            'revenue' => array_map('floatval', array_column($top_products_raw, 'REVENUE'))
        ];
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'inventory_summary' => $inventory_summary,
            'stock_data' => $stock_data,
            'low_stock' => $low_stock,
            'low_stock_count' => count($low_stock),
            'product_performance' => $product_performance,
            'top_products' => $top_products
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getClientReportData() {
    try {
        $db = Database::getInstance();
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // Client summary metrics
        $sql = "SELECT 
                    COUNT(DISTINCT c.CLIENT_NO) as total_clients,
                    COUNT(DISTINCT CASE WHEN i.INVOICE_DATE >= SYSDATE - 30 THEN c.CLIENT_NO END) as active_clients,
                    NVL(AVG(client_revenue.total_revenue), 0) as avg_client_value,
                    NVL(SUM(client_revenue.total_revenue), 0) as total_client_revenue
                FROM Clients c
                LEFT JOIN (
                    SELECT 
                        i.CLIENT_NO,
                        SUM(id.QTY * id.PRICE) as total_revenue
                    FROM INVOICES i
                    LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                    WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                          AND TO_DATE(:end_date, 'YYYY-MM-DD')
                    GROUP BY i.CLIENT_NO
                ) client_revenue ON c.CLIENT_NO = client_revenue.CLIENT_NO
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $client_summary = $db->fetchOne($stmt);
        
        // Top clients by revenue with enhanced metrics
        $sql = "SELECT 
                    c.CLIENTNAME as client_name,
                    ct.TYPE_NAME as client_type,
                    c.CITY as city,
                    c.DISCOUNT as client_discount,
                    ct.DISCOUNT_RATE as type_discount,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as profit,
                    COUNT(DISTINCT i.INVOICENO) as order_count,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_order_value
                FROM Clients c
                LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY c.CLIENTNAME, ct.TYPE_NAME, c.CITY, c.DISCOUNT, ct.DISCOUNT_RATE
                HAVING SUM(id.QTY * id.PRICE) > 0
                ORDER BY revenue DESC
                FETCH FIRST 10 ROWS ONLY";
        
        $stmt = $db->query($sql, $params);
        $top_clients_raw = $db->fetchAll($stmt);
        
        $top_clients = [
            'labels' => array_column($top_clients_raw, 'CLIENT_NAME'),
            'revenue' => array_map('floatval', array_column($top_clients_raw, 'REVENUE')),
            'profit' => array_map('floatval', array_column($top_clients_raw, 'PROFIT'))
        ];
        
        // Client types distribution with revenue analysis
        $sql = "SELECT 
                    ct.TYPE_NAME as client_type,
                    ct.DISCOUNT_RATE as type_discount,
                    COUNT(c.CLIENT_NO) as client_count,
                    COUNT(DISTINCT CASE WHEN i.INVOICE_DATE >= SYSDATE - 90 THEN c.CLIENT_NO END) as active_count,
                    NVL(SUM(id.QTY * id.PRICE), 0) as type_revenue,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_order_value
                FROM Client_Type ct
                LEFT JOIN Clients c ON ct.CLIENT_TYPE = c.CLIENT_TYPE
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO 
                    AND i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                    AND TO_DATE(:end_date, 'YYYY-MM-DD')
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                GROUP BY ct.TYPE_NAME, ct.DISCOUNT_RATE
                ORDER BY type_revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $client_types_raw = $db->fetchAll($stmt);
        
        $client_types = [
            'labels' => array_column($client_types_raw, 'CLIENT_TYPE'),
            'counts' => array_map('intval', array_column($client_types_raw, 'CLIENT_COUNT')),
            'revenue' => array_map('floatval', array_column($client_types_raw, 'TYPE_REVENUE')),
            'active_counts' => array_map('intval', array_column($client_types_raw, 'ACTIVE_COUNT'))
        ];
        
        // Geographic distribution
        $sql = "SELECT 
                    NVL(c.CITY, 'Unknown') as city,
                    COUNT(c.CLIENT_NO) as client_count,
                    NVL(SUM(id.QTY * id.PRICE), 0) as city_revenue
                FROM Clients c
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO 
                    AND i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                    AND TO_DATE(:end_date, 'YYYY-MM-DD')
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                GROUP BY c.CITY
                HAVING COUNT(c.CLIENT_NO) > 0
                ORDER BY city_revenue DESC
                FETCH FIRST 10 ROWS ONLY";
        
        $stmt = $db->query($sql, $params);
        $geographic_raw = $db->fetchAll($stmt);
        
        $geographic_data = [
            'labels' => array_column($geographic_raw, 'CITY'),
            'clients' => array_map('intval', array_column($geographic_raw, 'CLIENT_COUNT')),
            'revenue' => array_map('floatval', array_column($geographic_raw, 'CITY_REVENUE'))
        ];
        
        // Detailed client data with lifecycle information
        $sql = "SELECT 
                    c.CLIENTNAME as client_name,
                    ct.TYPE_NAME as client_type,
                    c.CITY as city,
                    c.PHONE as phone,
                    c.DISCOUNT as personal_discount,
                    ct.DISCOUNT_RATE as type_discount,
                    COUNT(i.INVOICENO) as total_orders,
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as total_cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as total_profit,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_order_value,
                    MIN(i.INVOICE_DATE) as first_order,
                    MAX(i.INVOICE_DATE) as last_order,
                    CASE 
                        WHEN MAX(i.INVOICE_DATE) >= SYSDATE - 30 THEN 'Active'
                        WHEN MAX(i.INVOICE_DATE) >= SYSDATE - 90 THEN 'Recent'
                        WHEN MAX(i.INVOICE_DATE) >= SYSDATE - 180 THEN 'Dormant'
                        ELSE 'Inactive'
                    END as lifecycle_status,
                    ROUND(SYSDATE - MAX(i.INVOICE_DATE), 0) as days_since_last_order
                FROM Clients c
                LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY c.CLIENTNAME, ct.TYPE_NAME, c.CITY, c.PHONE, c.DISCOUNT, ct.DISCOUNT_RATE
                ORDER BY total_revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $client_details = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'client_summary' => $client_summary,
            'top_clients' => $top_clients,
            'client_types' => $client_types,
            'geographic_data' => $geographic_data,
            'client_details' => $client_details
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getEmployeeReportData() {
    try {
        $db = Database::getInstance();
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // Employee summary metrics
        $sql = "SELECT 
                    COUNT(*) as total_employees,
                    COUNT(CASE WHEN i.INVOICE_DATE >= SYSDATE - 30 THEN e.EMPLOYEEID END) as active_sales_employees,
                    NVL(AVG(e.SALARY), 0) as avg_salary,
                    NVL(SUM(e.SALARY), 0) as total_payroll,
                    NVL(AVG(emp_sales.revenue), 0) as avg_employee_revenue
                FROM Employees e
                LEFT JOIN (
                    SELECT 
                        i.EMPLOYEEID,
                        SUM(id.QTY * id.PRICE) as revenue
                    FROM INVOICES i
                    LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                    WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                          AND TO_DATE(:end_date, 'YYYY-MM-DD')
                    GROUP BY i.EMPLOYEEID
                ) emp_sales ON e.EMPLOYEEID = emp_sales.EMPLOYEEID
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $employee_summary = $db->fetchOne($stmt);
        
        // Job title performance analysis
        $sql = "SELECT 
                    j.JOB_TITLE as job_title,
                    j.MIN_SALARY as min_salary,
                    j.MAX_SALARY as max_salary,
                    COUNT(e.EMPLOYEEID) as employee_count,
                    NVL(AVG(e.SALARY), 0) as avg_actual_salary,
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_revenue_per_employee,
                    COUNT(DISTINCT i.INVOICENO) as total_orders
                FROM JOBS j
                LEFT JOIN Employees e ON j.JOB_ID = e.JOB_ID
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID 
                    AND i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                    AND TO_DATE(:end_date, 'YYYY-MM-DD')
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                GROUP BY j.JOB_TITLE, j.MIN_SALARY, j.MAX_SALARY
                HAVING COUNT(e.EMPLOYEEID) > 0
                ORDER BY total_revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $job_performance = $db->fetchAll($stmt);
        
        // Employee performance for chart with enhanced metrics
        $sql = "SELECT 
                    e.EMPLOYEENAME as employee_name,
                    j.JOB_TITLE as job_title,
                    e.SALARY as salary,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as profit,
                    COUNT(DISTINCT i.INVOICENO) as orders_handled,
                    COUNT(DISTINCT i.CLIENT_NO) as clients_served,
                    CASE 
                        WHEN e.SALARY > 0 AND SUM(id.QTY * id.PRICE) > 0 
                        THEN ROUND(SUM(id.QTY * id.PRICE) / (e.SALARY / 12), 2)
                        ELSE 0 
                    END as revenue_to_salary_ratio
                FROM Employees e
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY e.EMPLOYEENAME, j.JOB_TITLE, e.SALARY
                HAVING SUM(id.QTY * id.PRICE) > 0
                ORDER BY revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $performance_raw = $db->fetchAll($stmt);
        
        $performance_data = [
            'labels' => array_column($performance_raw, 'EMPLOYEE_NAME'),
            'revenue' => array_map('floatval', array_column($performance_raw, 'REVENUE')),
            'profit' => array_map('floatval', array_column($performance_raw, 'PROFIT')),
            'salary' => array_map('floatval', array_column($performance_raw, 'SALARY'))
        ];
        
        // Top performers with enhanced information
        $top_performers = array_slice($performance_raw, 0, 5);
        foreach ($top_performers as &$performer) {
            // Calculate performance metrics
            $monthly_salary = $performer['SALARY'] / 12;
            $performer['monthly_salary'] = $monthly_salary;
            $performer['roi_ratio'] = $monthly_salary > 0 ? round($performer['PROFIT'] / $monthly_salary, 2) : 0;
            $performer['efficiency_score'] = $performer['ORDERS_HANDLED'] > 0 ? round($performer['REVENUE'] / $performer['ORDERS_HANDLED'], 2) : 0;
        }
        
        // Salary vs Performance analysis
        $sql = "SELECT 
                    j.JOB_TITLE as job_title,
                    COUNT(e.EMPLOYEEID) as employee_count,
                    NVL(AVG(e.SALARY), 0) as avg_salary,
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    CASE 
                        WHEN AVG(e.SALARY) > 0 
                        THEN ROUND(SUM(id.QTY * id.PRICE) / AVG(e.SALARY), 2)
                        ELSE 0 
                    END as revenue_per_salary_dollar
                FROM JOBS j
                LEFT JOIN Employees e ON j.JOB_ID = e.JOB_ID
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID 
                    AND i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                    AND TO_DATE(:end_date, 'YYYY-MM-DD')
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                GROUP BY j.JOB_TITLE
                HAVING COUNT(e.EMPLOYEEID) > 0
                ORDER BY revenue_per_salary_dollar DESC";
        
        $stmt = $db->query($sql, $params);
        $salary_analysis_raw = $db->fetchAll($stmt);
        
        $salary_analysis = [
            'labels' => array_column($salary_analysis_raw, 'JOB_TITLE'),
            'avg_salary' => array_map('floatval', array_column($salary_analysis_raw, 'AVG_SALARY')),
            'total_revenue' => array_map('floatval', array_column($salary_analysis_raw, 'TOTAL_REVENUE')),
            'efficiency' => array_map('floatval', array_column($salary_analysis_raw, 'REVENUE_PER_SALARY_DOLLAR'))
        ];
        
        // Detailed employee data with comprehensive metrics
        $sql = "SELECT 
                    e.EMPLOYEENAME as employee_name,
                    j.JOB_TITLE as job_title,
                    e.GENDER as gender,
                    ROUND((SYSDATE - e.BIRTHDATE) / 365.25, 1) as age,
                    e.SALARY as annual_salary,
                    ROUND(e.SALARY / 12, 2) as monthly_salary,
                    COUNT(i.INVOICENO) as total_sales,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as profit,
                    CASE 
                        WHEN COUNT(i.INVOICENO) > 0 
                        THEN ROUND(SUM(id.QTY * id.PRICE) / COUNT(i.INVOICENO), 2)
                        ELSE 0 
                    END as avg_order_value,
                    COUNT(DISTINCT i.CLIENT_NO) as clients_served,
                    CASE 
                        WHEN e.SALARY > 0 
                        THEN ROUND((SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE)) / (e.SALARY / 12), 2)
                        ELSE 0 
                    END as monthly_roi_ratio,
                    CASE 
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 50000 THEN 'Excellent'
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 25000 THEN 'Very Good'
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 10000 THEN 'Good'
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 5000 THEN 'Average'
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 0 THEN 'Below Average'
                        ELSE 'No Sales'
                    END as performance_rating
                FROM Employees e
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY e.EMPLOYEENAME, j.JOB_TITLE, e.GENDER, e.BIRTHDATE, e.SALARY
                ORDER BY revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $detailed_data = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'employee_summary' => $employee_summary,
            'job_performance' => $job_performance,
            'performance_data' => $performance_data,
            'top_performers' => $top_performers,
            'salary_analysis' => $salary_analysis,
            'detailed_data' => $detailed_data
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getFinancialReportData() {
    try {
        $db = Database::getInstance();
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        
        // Financial overview summary
        $sql = "SELECT 
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as total_cogs,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as gross_profit,
                    NVL(SUM(e.SALARY / 12), 0) as monthly_payroll_cost,
                    CASE 
                        WHEN SUM(id.QTY * id.PRICE) > 0 
                        THEN ROUND(((SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE)) / SUM(id.QTY * id.PRICE)) * 100, 2)
                        ELSE 0 
                    END as gross_margin_percent,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_transaction_value,
                    COUNT(DISTINCT i.INVOICENO) as total_transactions,
                    COUNT(DISTINCT i.CLIENT_NO) as unique_customers,
                    NVL(SUM(id.QTY), 0) as total_units_sold
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $financial_summary = $db->fetchOne($stmt);
        
        // Monthly financial trends
        $sql = "SELECT 
                    TO_CHAR(i.INVOICE_DATE, 'YYYY-MM') as month,
                    NVL(SUM(id.QTY * id.PRICE), 0) as monthly_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as monthly_cogs,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as monthly_profit,
                    COUNT(DISTINCT i.INVOICENO) as monthly_transactions,
                    NVL(AVG(id.QTY * id.PRICE), 0) as avg_order_value
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY TO_CHAR(i.INVOICE_DATE, 'YYYY-MM')
                ORDER BY month";
        
        $stmt = $db->query($sql, $params);
        $monthly_trends_raw = $db->fetchAll($stmt);
        
        $monthly_trends = [
            'labels' => array_column($monthly_trends_raw, 'MONTH'),
            'revenue' => array_map('floatval', array_column($monthly_trends_raw, 'MONTHLY_REVENUE')),
            'cogs' => array_map('floatval', array_column($monthly_trends_raw, 'MONTHLY_COGS')),
            'profit' => array_map('floatval', array_column($monthly_trends_raw, 'MONTHLY_PROFIT')),
            'transactions' => array_map('intval', array_column($monthly_trends_raw, 'MONTHLY_TRANSACTIONS'))
        ];
        
        // Product category profitability
        $sql = "SELECT 
                    pt.PRODUCTTYPE_NAME as category,
                    NVL(SUM(id.QTY * id.PRICE), 0) as category_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as category_cogs,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as category_profit,
                    NVL(SUM(id.QTY), 0) as units_sold,
                    CASE 
                        WHEN SUM(id.QTY * id.PRICE) > 0 
                        THEN ROUND(((SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE)) / SUM(id.QTY * id.PRICE)) * 100, 2)
                        ELSE 0 
                    END as profit_margin_percent,
                    COUNT(DISTINCT p.PRODUCT_NO) as products_in_category
                FROM Product_Type pt
                LEFT JOIN Products p ON pt.PRODUCTTYPE_ID = p.PRODUCTTYPE
                LEFT JOIN INVOICE_DETAILS id ON p.PRODUCT_NO = id.PRODUCT_NO
                LEFT JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY pt.PRODUCTTYPE_NAME
                HAVING SUM(id.QTY * id.PRICE) > 0
                ORDER BY category_profit DESC";
        
        $stmt = $db->query($sql, $params);
        $category_profitability = $db->fetchAll($stmt);
        
        // Customer profitability analysis
        $sql = "SELECT 
                    ct.TYPE_NAME as client_type,
                    COUNT(DISTINCT c.CLIENT_NO) as client_count,
                    NVL(SUM(id.QTY * id.PRICE), 0) as type_revenue,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as type_cogs,
                    NVL(SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE), 0) as type_profit,
                    NVL(AVG(c.DISCOUNT), 0) as avg_discount_given,
                    CASE 
                        WHEN COUNT(DISTINCT c.CLIENT_NO) > 0 
                        THEN ROUND(SUM(id.QTY * id.PRICE) / COUNT(DISTINCT c.CLIENT_NO), 2)
                        ELSE 0 
                    END as revenue_per_client
                FROM Client_Type ct
                LEFT JOIN Clients c ON ct.CLIENT_TYPE = c.CLIENT_TYPE
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY ct.TYPE_NAME
                HAVING SUM(id.QTY * id.PRICE) > 0
                ORDER BY type_profit DESC";
        
        $stmt = $db->query($sql, $params);
        $customer_profitability = $db->fetchAll($stmt);
        
        // Cost analysis breakdown
        $sql = "SELECT 
                    'Product Costs' as cost_category,
                    NVL(SUM(id.QTY * p.COST_PRICE), 0) as cost_amount
                FROM INVOICE_DETAILS id
                JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                UNION ALL
                SELECT 
                    'Employee Salaries' as cost_category,
                    NVL(SUM(DISTINCT e.SALARY) / 12, 0) as cost_amount
                FROM Employees e
                JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')";
        
        $stmt = $db->query($sql, $params);
        $cost_breakdown_raw = $db->fetchAll($stmt);
        
        $cost_breakdown = [
            'labels' => array_column($cost_breakdown_raw, 'COST_CATEGORY'),
            'values' => array_map('floatval', array_column($cost_breakdown_raw, 'COST_AMOUNT'))
        ];
        
        // ROI and efficiency metrics
        $sql = "SELECT 
                    pt.PRODUCTTYPE_NAME as category,
                    NVL(SUM(p.QTY_ON_HAND * p.COST_PRICE), 0) as inventory_investment,
                    NVL(SUM(id.QTY * id.PRICE), 0) as sales_revenue,
                    CASE 
                        WHEN SUM(p.QTY_ON_HAND * p.COST_PRICE) > 0 
                        THEN ROUND(SUM(id.QTY * id.PRICE) / SUM(p.QTY_ON_HAND * p.COST_PRICE), 2)
                        ELSE 0 
                    END as inventory_turnover_ratio,
                    CASE 
                        WHEN SUM(p.QTY_ON_HAND * p.COST_PRICE) > 0 
                        THEN ROUND(((SUM(id.QTY * id.PRICE) - SUM(id.QTY * p.COST_PRICE)) / SUM(p.QTY_ON_HAND * p.COST_PRICE)) * 100, 2)
                        ELSE 0 
                    END as roi_percent
                FROM Product_Type pt
                LEFT JOIN Products p ON pt.PRODUCTTYPE_ID = p.PRODUCTTYPE
                LEFT JOIN INVOICE_DETAILS id ON p.PRODUCT_NO = id.PRODUCT_NO
                LEFT JOIN INVOICES i ON id.INVOICENO = i.INVOICENO
                    AND i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                    AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY pt.PRODUCTTYPE_NAME
                HAVING SUM(p.QTY_ON_HAND * p.COST_PRICE) > 0
                ORDER BY roi_percent DESC";
        
        $stmt = $db->query($sql, $params);
        $roi_analysis = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'financial_summary' => $financial_summary,
            'monthly_trends' => $monthly_trends,
            'category_profitability' => $category_profitability,
            'customer_profitability' => $customer_profitability,
            'cost_breakdown' => $cost_breakdown,
            'roi_analysis' => $roi_analysis
        ]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Handle AJAX requests for report data
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'sales_report':
            getSalesReportData();
            break;
        case 'inventory_report':
            getInventoryReportData();
            break;
        case 'client_report':
            getClientReportData();
            break;
        case 'employee_report':
            getEmployeeReportData();
            break;
        case 'financial_report':
            getFinancialReportData();
            break;
    }
}

require_once 'includes/header.php';

// Set default date range (last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Get date range from request if provided
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}
?>

<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-chart-line me-2"></i>Business Reports Dashboard</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf me-1"></i>Export PDF
                    </button>
                    <button class="btn btn-outline-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel me-1"></i>Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('today')">Today</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('week')">This Week</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('month')">This Month</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="setDateRange('year')">This Year</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-primary mb-1">Total Revenue</h6>
                            <h4 class="mb-0" id="total-revenue">Loading...</h4>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-success mb-1">Total Invoices</h6>
                            <h4 class="mb-0" id="total-invoices">Loading...</h4>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-file-invoice fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-info mb-1">Active Clients</h6>
                            <h4 class="mb-0" id="active-clients">Loading...</h4>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-warning mb-1">Low Stock Items</h6>
                            <h4 class="mb-0" id="low-stock">Loading...</h4>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Navigation Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="reportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" 
                                    type="button" role="tab" aria-controls="sales" aria-selected="true">
                                <i class="fas fa-chart-bar me-1"></i>Sales Reports
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" 
                                    type="button" role="tab" aria-controls="inventory" aria-selected="false">
                                <i class="fas fa-boxes me-1"></i>Inventory Reports
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="clients-tab" data-bs-toggle="tab" data-bs-target="#clients" 
                                    type="button" role="tab" aria-controls="clients" aria-selected="false">
                                <i class="fas fa-user-tie me-1"></i>Client Analytics
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" 
                                    type="button" role="tab" aria-controls="employees" aria-selected="false">
                                <i class="fas fa-users-cog me-1"></i>Employee Performance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="financial-tab" data-bs-toggle="tab" data-bs-target="#financial" 
                                    type="button" role="tab" aria-controls="financial" aria-selected="false">
                                <i class="fas fa-calculator me-1"></i>Financial Analysis
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="reportTabsContent">
                        
                        <!-- Sales Reports Tab -->
                        <div class="tab-pane fade show active" id="sales" role="tabpanel" aria-labelledby="sales-tab">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-line me-2"></i>Sales Trend</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="salesChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-pie-chart me-2"></i>Sales by Status</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="statusChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-table me-2"></i>Detailed Sales Report</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="salesTable" class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Invoice No</th>
                                                            <th>Client</th>
                                                            <th>Employee</th>
                                                            <th>Status</th>
                                                            <th>Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="salesTableBody">
                                                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inventory Reports Tab -->
                        <div class="tab-pane fade" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-bar me-2"></i>Stock Levels</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="stockChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alerts</h5>
                                        </div>
                                        <div class="card-body" id="lowStockAlerts">
                                            Loading...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-table me-2"></i>Product Performance</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="inventoryTable" class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Product</th>
                                                            <th>Category</th>
                                                            <th>Stock Level</th>
                                                            <th>Reorder Level</th>
                                                            <th>Units Sold</th>
                                                            <th>Revenue</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="inventoryTableBody">
                                                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Client Analytics Tab -->
                        <div class="tab-pane fade" id="clients" role="tabpanel" aria-labelledby="clients-tab">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-bar me-2"></i>Top Clients by Revenue</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="clientChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-pie-chart me-2"></i>Client Types</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="clientTypeChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-table me-2"></i>Client Performance Report</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="clientTable" class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Client Name</th>
                                                            <th>Type</th>
                                                            <th>Total Orders</th>
                                                            <th>Total Revenue</th>
                                                            <th>Last Order</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="clientTableBody">
                                                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Performance Tab -->
                        <div class="tab-pane fade" id="employees" role="tabpanel" aria-labelledby="employees-tab">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-bar me-2"></i>Employee Sales Performance</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="employeeChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-trophy me-2"></i>Top Performers</h5>
                                        </div>
                                        <div class="card-body" id="topPerformers">
                                            Loading...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-table me-2"></i>Employee Performance Details</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="employeeTable" class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Employee</th>
                                                            <th>Job Title</th>
                                                            <th>Total Sales</th>
                                                            <th>Revenue</th>
                                                            <th>Avg Order Value</th>
                                                            <th>Performance</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="employeeTableBody">
                                                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Analysis Tab -->
                        <div class="tab-pane fade" id="financial" role="tabpanel" aria-labelledby="financial-tab">
                            <div class="row mb-4">
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card text-center border-success">
                                        <div class="card-body">
                                            <h6 class="text-success">Gross Profit</h6>
                                            <h4 id="gross-profit">Loading...</h4>
                                            <small class="text-muted">Margin: <span id="gross-margin">0%</span></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card text-center border-info">
                                        <div class="card-body">
                                            <h6 class="text-info">Cost of Goods</h6>
                                            <h4 id="total-cogs">Loading...</h4>
                                            <small class="text-muted">COGS</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card text-center border-warning">
                                        <div class="card-body">
                                            <h6 class="text-warning">Avg Transaction</h6>
                                            <h4 id="avg-transaction">Loading...</h4>
                                            <small class="text-muted">Per Order</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card text-center border-primary">
                                        <div class="card-body">
                                            <h6 class="text-primary">Units Sold</h6>
                                            <h4 id="total-units">Loading...</h4>
                                            <small class="text-muted">Total Items</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-line me-2"></i>Monthly Financial Trends</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="financialTrendChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-pie-chart me-2"></i>Cost Breakdown</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="costBreakdownChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-bar me-2"></i>Category Profitability</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="categoryProfitChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-chart-bar me-2"></i>Client Type ROI</h5>
                                        </div>
                                        <div class="card-body">
                                            <canvas id="clientTypeROIChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5><i class="fas fa-table me-2"></i>Financial Performance Analysis</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="financialTable" class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Category</th>
                                                            <th>Revenue</th>
                                                            <th>COGS</th>
                                                            <th>Gross Profit</th>
                                                            <th>Margin %</th>
                                                            <th>Units Sold</th>
                                                            <th>Products</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="financialTableBody">
                                                        <tr><td colspan="7" class="text-center">Loading...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global variables for charts
let salesChart, statusChart, stockChart, clientChart, clientTypeChart, employeeChart;
let financialTrendChart, costBreakdownChart, categoryProfitChart, clientTypeROIChart;

// Initialize reports when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadSummaryData();
    loadSalesReport();
    
    // Add event listeners for tab switches
    document.querySelectorAll('#reportTabs .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const targetTab = e.target.getAttribute('data-bs-target');
            switch(targetTab) {
                case '#sales':
                    loadSalesReport();
                    break;
                case '#inventory':
                    loadInventoryReport();
                    break;
                case '#clients':
                    loadClientReport();
                    break;
                case '#employees':
                    loadEmployeeReport();
                    break;
                case '#financial':
                    loadFinancialReport();
                    break;
            }
        });
    });
});

// Date range preset functions
function setDateRange(period) {
    const today = new Date();
    let startDate, endDate = today;
    
    switch(period) {
        case 'today':
            startDate = today;
            break;
        case 'week':
            startDate = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            break;
        case 'year':
            startDate = new Date(today.getFullYear(), 0, 1);
            break;
    }
    
    document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
    document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
}

// Load summary data for cards
function loadSummaryData() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    fetch(`reports.php?action=sales_report&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.summary) {
                // Oracle returns uppercase field names
                document.getElementById('total-revenue').textContent = '$' + (data.summary.TOTAL_REVENUE || 0).toLocaleString();
                document.getElementById('total-invoices').textContent = data.summary.TOTAL_INVOICES || 0;
                document.getElementById('active-clients').textContent = data.summary.ACTIVE_CLIENTS || 0;
            } else {
                console.error('Failed to load summary data:', data);
            }
        })
        .catch(error => console.error('Error loading summary:', error));
        
    fetch(`reports.php?action=inventory_report`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Oracle returns uppercase field names  
                document.getElementById('low-stock').textContent = data.low_stock_count || data.LOW_STOCK_COUNT || 0;
            }
        })
        .catch(error => console.error('Error loading inventory summary:', error));
}

// Load sales report
function loadSalesReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    fetch(`reports.php?action=sales_report&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSalesChart(data.chart_data || {labels: [], revenue: [], profit: [], orders: []});
                updateStatusChart(data.status_data || {labels: [], counts: [], revenues: []});
                updateSalesTable(data.detailed_data || []);
            } else {
                console.error('Sales report error:', data.message);
            }
        })
        .catch(error => console.error('Error loading sales report:', error));
}

// Load inventory report
function loadInventoryReport() {
    fetch('reports.php?action=inventory_report')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStockChart(data.stock_data || {labels: [], stock_levels: [], values: [], margins: []});
                updateLowStockAlerts(data.low_stock || []);
                updateInventoryTable(data.product_performance || []);
            } else {
                console.error('Inventory report error:', data.message);
            }
        })
        .catch(error => console.error('Error loading inventory report:', error));
}

// Load client report
function loadClientReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    fetch(`reports.php?action=client_report&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateClientChart(data.top_clients || {labels: [], revenue: [], profit: []});
                updateClientTypeChart(data.client_types || {labels: [], counts: [], revenue: [], active_counts: []});
                updateClientTable(data.client_details || []);
            } else {
                console.error('Client report error:', data.message);
            }
        })
        .catch(error => console.error('Error loading client report:', error));
}

// Load employee report
function loadEmployeeReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    fetch(`reports.php?action=employee_report&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateEmployeeChart(data.performance_data || {labels: [], revenue: [], profit: [], salary: []});
                updateTopPerformers(data.top_performers || []);
                updateEmployeeTable(data.detailed_data || []);
            } else {
                console.error('Employee report error:', data.message);
            }
        })
        .catch(error => console.error('Error loading employee report:', error));
}

// Load financial report
function loadFinancialReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    fetch(`reports.php?action=financial_report&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFinancialSummary(data.financial_summary || {});
                updateFinancialTrendChart(data.monthly_trends || {labels: [], revenue: [], cogs: [], profit: [], transactions: []});
                updateCostBreakdownChart(data.cost_breakdown || {labels: [], values: []});
                updateCategoryProfitChart(data.category_profitability || []);
                updateClientTypeROIChart(data.customer_profitability || []);
                updateFinancialTable(data.category_profitability || []);
            } else {
                console.error('Financial report error:', data.message);
            }
        })
        .catch(error => console.error('Error loading financial report:', error));
}

// Chart update functions
function updateSalesChart(data) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (salesChart) {
        salesChart.destroy();
    }
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Daily Revenue',
                data: data.revenue || data.values, // Handle both old and new format
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                yAxisID: 'y'
            }, {
                label: 'Daily Profit',
                data: data.profit || [],
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                yAxisID: 'y'
            }, {
                label: 'Daily Orders',
                data: data.orders || [],
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value + ' orders';
                        }
                    }
                }
            }
        }
    });
}

function updateStatusChart(data) {
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    if (statusChart) {
        statusChart.destroy();
    }
    
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.counts || data.values, // Handle both old and new format
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            if (data.revenues && data.revenues[context.dataIndex]) {
                                return 'Revenue: $' + data.revenues[context.dataIndex].toLocaleString();
                            }
                            return '';
                        }
                    }
                }
            }
        }
    });
}

function updateStockChart(data) {
    const ctx = document.getElementById('stockChart').getContext('2d');
    
    if (stockChart) {
        stockChart.destroy();
    }
    
    stockChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Stock Level',
                data: data.stock_levels,
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }, {
                label: 'Reorder Level',
                data: data.reorder_levels,
                backgroundColor: 'rgba(255, 99, 132, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function updateClientChart(data) {
    const ctx = document.getElementById('clientChart').getContext('2d');
    
    if (clientChart) {
        clientChart.destroy();
    }
    
    clientChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Revenue',
                data: data.revenue || data.values, // Handle both formats
                backgroundColor: 'rgba(75, 192, 192, 0.8)'
            }, {
                label: 'Profit',
                data: data.profit || [],
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function updateClientTypeChart(data) {
    const ctx = document.getElementById('clientTypeChart').getContext('2d');
    
    if (clientTypeChart) {
        clientTypeChart.destroy();
    }
    
    clientTypeChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.counts || data.values, // Handle both formats
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const revenue = data.revenue ? data.revenue[context.dataIndex] : 0;
                            const activeCount = data.active_counts ? data.active_counts[context.dataIndex] : 0;
                            let result = '';
                            if (revenue > 0) {
                                result += '\nRevenue: $' + revenue.toLocaleString();
                            }
                            if (activeCount > 0) {
                                result += '\nActive: ' + activeCount;
                            }
                            return result;
                        }
                    }
                }
            }
        }
    });
}

function updateEmployeeChart(data) {
    const ctx = document.getElementById('employeeChart').getContext('2d');
    
    if (employeeChart) {
        employeeChart.destroy();
    }
    
    employeeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Sales Revenue',
                data: data.revenue || data.values, // Handle both formats
                backgroundColor: 'rgba(153, 102, 255, 0.8)',
                yAxisID: 'y'
            }, {
                label: 'Gross Profit',
                data: data.profit || [],
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                yAxisID: 'y'
            }, {
                label: 'Annual Salary',
                data: data.salary || [],
                backgroundColor: 'rgba(255, 206, 86, 0.8)',
                yAxisID: 'y'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Financial chart functions
function updateFinancialSummary(data) {
    if (data && typeof data === 'object') {
        // Oracle returns uppercase field names
        const grossProfit = parseFloat(data.TOTAL_REVENUE || 0) - parseFloat(data.TOTAL_COGS || 0);
        document.getElementById('gross-profit').textContent = '$' + grossProfit.toLocaleString();
        document.getElementById('gross-margin').textContent = (data.GROSS_MARGIN_PERCENT || 0) + '%';
        document.getElementById('total-cogs').textContent = '$' + parseFloat(data.TOTAL_COGS || 0).toLocaleString();
        document.getElementById('avg-transaction').textContent = '$' + parseFloat(data.AVG_TRANSACTION_VALUE || 0).toLocaleString();
        document.getElementById('total-units').textContent = parseInt(data.TOTAL_UNITS_SOLD || 0).toLocaleString();
    }
}

function updateFinancialTrendChart(data) {
    const ctx = document.getElementById('financialTrendChart').getContext('2d');
    
    if (financialTrendChart) {
        financialTrendChart.destroy();
    }
    
    financialTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Revenue',
                data: data.revenue,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'COGS',
                data: data.cogs,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }, {
                label: 'Profit',
                data: data.profit,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

function updateCostBreakdownChart(data) {
    const ctx = document.getElementById('costBreakdownChart').getContext('2d');
    
    if (costBreakdownChart) {
        costBreakdownChart.destroy();
    }
    
    costBreakdownChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function updateCategoryProfitChart(data) {
    const ctx = document.getElementById('categoryProfitChart').getContext('2d');
    
    if (categoryProfitChart) {
        categoryProfitChart.destroy();
    }
    
    // Oracle returns uppercase field names
    const labels = data.map(item => item.CATEGORY);
    const profits = data.map(item => parseFloat(item.CATEGORY_PROFIT || 0));
    const margins = data.map(item => parseFloat(item.PROFIT_MARGIN_PERCENT || 0));
    
    categoryProfitChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Profit ($)',
                data: profits,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                yAxisID: 'y'
            }, {
                label: 'Margin (%)',
                data: margins,
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });
}

function updateClientTypeROIChart(data) {
    const ctx = document.getElementById('clientTypeROIChart').getContext('2d');
    
    if (clientTypeROIChart) {
        clientTypeROIChart.destroy();
    }
    
    // Oracle returns uppercase field names
    const labels = data.map(item => item.CLIENT_TYPE);
    const revenues = data.map(item => parseFloat(item.TYPE_REVENUE || 0));
    const profits = data.map(item => parseFloat(item.TYPE_PROFIT || 0));
    
    clientTypeROIChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: revenues,
                backgroundColor: 'rgba(54, 162, 235, 0.8)'
            }, {
                label: 'Profit',
                data: profits,
                backgroundColor: 'rgba(75, 192, 192, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Table update functions
function updateSalesTable(data) {
    const tbody = document.getElementById('salesTableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No sales data available for the selected date range</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        // Oracle returns uppercase field names
        tr.innerHTML = `
            <td>${new Date(row.INVOICE_DATE).toLocaleDateString()}</td>
            <td>${row.INVOICE_NO}</td>
            <td>${row.CLIENT_NAME}<br><small class="text-muted">${row.CLIENT_TYPE || ''}</small></td>
            <td>${row.EMPLOYEE_NAME}<br><small class="text-muted">${row.EMPLOYEE_JOB || ''}</small></td>
            <td><span class="badge bg-${getStatusColor(row.STATUS)}">${row.STATUS}</span></td>
            <td>$${parseFloat(row.AMOUNT || 0).toLocaleString()}<br>
                <small class="text-success">Profit: $${parseFloat(row.PROFIT || 0).toLocaleString()}</small></td>
        `;
        tbody.appendChild(tr);
    });
}

function updateInventoryTable(data) {
    const tbody = document.getElementById('inventoryTableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No inventory data available</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        // Oracle returns uppercase field names
        const stockStatus = row.STOCK_LEVEL <= row.REORDER_LEVEL ? 'text-danger' : 'text-success';
        
        tr.innerHTML = `
            <td>${row.PRODUCT_NAME}<br><small class="text-muted">$${parseFloat(row.UNIT_PRICE || 0).toFixed(2)}</small></td>
            <td>${row.CATEGORY}</td>
            <td><span class="${stockStatus}">${row.STOCK_LEVEL}</span></td>
            <td>${row.REORDER_LEVEL}</td>
            <td>${row.UNITS_SOLD}<br><small class="text-muted">Turnover: ${row.TURNOVER_RATIO || 0}</small></td>
            <td>$${parseFloat(row.REVENUE || 0).toLocaleString()}<br>
                <small class="text-success">Profit: $${parseFloat(row.GROSS_PROFIT || 0).toLocaleString()}</small></td>
        `;
        tbody.appendChild(tr);
    });
}

function updateFinancialTable(data) {
    const tbody = document.getElementById('financialTableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No financial data available</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        // Oracle returns uppercase field names
        tr.innerHTML = `
            <td>${row.CATEGORY}</td>
            <td>$${parseFloat(row.CATEGORY_REVENUE || 0).toLocaleString()}</td>
            <td>$${parseFloat(row.CATEGORY_COGS || 0).toLocaleString()}</td>
            <td>$${parseFloat(row.CATEGORY_PROFIT || 0).toLocaleString()}</td>
            <td><span class="badge bg-${getProfitMarginColor(row.PROFIT_MARGIN_PERCENT)}">${parseFloat(row.PROFIT_MARGIN_PERCENT || 0).toFixed(1)}%</span></td>
            <td>${parseInt(row.UNITS_SOLD || 0).toLocaleString()}</td>
            <td>${row.PRODUCTS_IN_CATEGORY || 0}</td>
        `;
        tbody.appendChild(tr);
    });
}

function updateClientTable(data) {
    const tbody = document.getElementById('clientTableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No client data available</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        // Oracle returns uppercase field names
        const statusColor = getClientStatusColor(row.LIFECYCLE_STATUS || row.STATUS);
        const daysSince = row.DAYS_SINCE_LAST_ORDER ? `(${row.DAYS_SINCE_LAST_ORDER} days ago)` : '';
        
        tr.innerHTML = `
            <td>${row.CLIENT_NAME}<br><small class="text-muted">${row.CITY || ''} ${row.PHONE || ''}</small></td>
            <td>${row.CLIENT_TYPE}<br><small class="text-info">Discount: ${parseFloat(row.PERSONAL_DISCOUNT || row.TYPE_DISCOUNT || 0).toFixed(1)}%</small></td>
            <td>${row.TOTAL_ORDERS}<br><small class="text-muted">Avg: $${parseFloat(row.AVG_ORDER_VALUE || 0).toLocaleString()}</small></td>
            <td>$${parseFloat(row.TOTAL_REVENUE || 0).toLocaleString()}<br><small class="text-success">Profit: $${parseFloat(row.TOTAL_PROFIT || 0).toLocaleString()}</small></td>
            <td>${row.LAST_ORDER ? new Date(row.LAST_ORDER).toLocaleDateString() : 'N/A'}<br><small class="text-muted">${daysSince}</small></td>
            <td><span class="badge bg-${statusColor}">${row.LIFECYCLE_STATUS || row.STATUS}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

function updateEmployeeTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No employee data available</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        // Oracle returns uppercase field names
        const roiRatio = row.MONTHLY_ROI_RATIO || 0;
        const roiColor = roiRatio >= 2 ? 'success' : roiRatio >= 1 ? 'warning' : 'danger';
        
        tr.innerHTML = `
            <td>${row.EMPLOYEE_NAME}<br><small class="text-muted">${row.GENDER || ''}, Age: ${row.AGE || 'N/A'}</small></td>
            <td>${row.JOB_TITLE}<br><small class="text-info">$${parseFloat(row.MONTHLY_SALARY || 0).toLocaleString()}/mo</small></td>
            <td>${row.TOTAL_SALES}<br><small class="text-muted">${row.CLIENTS_SERVED || 0} clients</small></td>
            <td>$${parseFloat(row.REVENUE || 0).toLocaleString()}<br><small class="text-success">Profit: $${parseFloat(row.PROFIT || 0).toLocaleString()}</small></td>
            <td>$${parseFloat(row.AVG_ORDER_VALUE || 0).toLocaleString()}<br><small class="text-info">ROI: <span class="badge bg-${roiColor}">${parseFloat(roiRatio).toFixed(1)}x</span></small></td>
            <td><span class="badge bg-${getPerformanceColor(row.PERFORMANCE_RATING || row.PERFORMANCE)}">${row.PERFORMANCE_RATING || row.PERFORMANCE}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

// Helper functions
function updateLowStockAlerts(data) {
    const container = document.getElementById('lowStockAlerts');
    container.innerHTML = '';
    
    if (data.length === 0) {
        container.innerHTML = '<p class="text-success"><i class="fas fa-check-circle me-2"></i>All products are sufficiently stocked!</p>';
        return;
    }
    
    data.forEach(item => {
        const alert = document.createElement('div');
        // Oracle returns uppercase field names
        const urgencyClass = getUrgencyClass(item.URGENCY_LEVEL);
        const reorderCost = item.REORDER_COST ? `Cost: $${parseFloat(item.REORDER_COST).toLocaleString()}` : '';
        
        alert.className = `alert alert-${urgencyClass} d-flex justify-content-between align-items-center mb-2`;
        alert.innerHTML = `
            <div>
                <strong>${item.PRODUCT_NAME}</strong> <span class="badge bg-secondary">${item.CATEGORY || ''}</span><br>
                <small>Stock: ${item.STOCK_LEVEL} | Reorder: ${item.REORDER_LEVEL} | Need: ${item.UNITS_NEEDED || 0}</small><br>
                <small class="text-muted">${reorderCost}</small>
            </div>
            <div class="text-end">
                <span class="badge bg-${urgencyClass === 'danger' ? 'danger' : 'warning'}">${item.URGENCY_LEVEL || 'Low'}</span><br>
                <small>$${parseFloat(item.UNIT_PRICE || 0).toFixed(2)}</small>
            </div>
        `;
        container.appendChild(alert);
    });
}

function updateTopPerformers(data) {
    const container = document.getElementById('topPerformers');
    container.innerHTML = '';
    
    data.forEach((performer, index) => {
        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center mb-3 p-2 border rounded';
        const roiRatio = performer.roi_ratio || 0;
        const roiColor = roiRatio >= 2 ? 'success' : roiRatio >= 1 ? 'warning' : 'danger';
        
        item.innerHTML = `
            <div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-${index === 0 ? 'warning' : index === 1 ? 'secondary' : 'dark'} me-2">
                        ${index + 1}
                    </span>
                    <strong>${performer.employee_name || performer.EMPLOYEE_NAME}</strong>
                </div>
                <small class="text-muted">${performer.job_title || 'N/A'}</small><br>
                <small class="text-info">Monthly: $${parseFloat(performer.monthly_salary || 0).toLocaleString()}</small>
            </div>
            <div class="text-end">
                <div><strong>$${parseFloat(performer.revenue || performer.REVENUE).toLocaleString()}</strong></div>
                <small class="text-muted">${performer.total_sales || performer.ORDERS_HANDLED || 0} orders</small><br>
                <small class="text-${roiColor}">ROI: ${parseFloat(roiRatio).toFixed(1)}x</small>
            </div>
        `;
        container.appendChild(item);
    });
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getPerformanceColor(performance) {
    switch(performance.toLowerCase()) {
        case 'excellent': return 'success';
        case 'good': return 'info';
        case 'average': return 'warning';
        case 'poor': return 'danger';
        default: return 'secondary';
    }
}

function getMovementStatusBadge(status) {
    switch(status ? status.toLowerCase() : '') {
        case 'active': return '<span class="badge bg-success">Active</span>';
        case 'slow moving': return '<span class="badge bg-warning">Slow</span>';
        case 'dead stock': return '<span class="badge bg-danger">Dead</span>';
        default: return '<span class="badge bg-secondary">Unknown</span>';
    }
}

function getProfitMarginColor(margin) {
    const marginNum = parseFloat(margin);
    if (marginNum >= 30) return 'success';
    if (marginNum >= 20) return 'info';
    if (marginNum >= 10) return 'warning';
    return 'danger';
}

function getClientStatusColor(status) {
    switch(status ? status.toLowerCase() : '') {
        case 'active': return 'success';
        case 'recent': return 'info';
        case 'dormant': return 'warning';
        case 'inactive': return 'danger';
        default: return 'secondary';
    }
}

function getUrgencyClass(urgency) {
    switch(urgency ? urgency.toLowerCase() : '') {
        case 'out of stock': return 'danger';
        case 'critical': return 'danger';
        case 'low stock': return 'warning';
        default: return 'warning';
    }
}

// Export functions
function exportToPDF() {
    const activeTab = document.querySelector('#reportTabs .nav-link.active').getAttribute('data-bs-target');
    let reportType = 'All Reports';
    
    switch(activeTab) {
        case '#sales': reportType = 'Sales Report'; break;
        case '#inventory': reportType = 'Inventory Report'; break;
        case '#clients': reportType = 'Client Report'; break;
        case '#employees': reportType = 'Employee Report'; break;
        case '#financial': reportType = 'Financial Analysis'; break;
    }
    
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    // Create a form to submit export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_reports.php';
    form.target = '_blank';
    
    const fields = {
        'export_type': 'pdf',
        'report_type': reportType,
        'start_date': startDate,
        'end_date': endDate,
        'active_tab': activeTab.replace('#', '')
    };
    
    Object.keys(fields).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    Swal.fire({
        title: 'Exporting to PDF',
        text: 'Your report is being generated and will open in a new tab',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}

function exportToExcel() {
    const activeTab = document.querySelector('#reportTabs .nav-link.active').getAttribute('data-bs-target');
    let reportType = 'All Reports';
    
    switch(activeTab) {
        case '#sales': reportType = 'Sales Report'; break;
        case '#inventory': reportType = 'Inventory Report'; break;
        case '#clients': reportType = 'Client Report'; break;
        case '#employees': reportType = 'Employee Report'; break;
        case '#financial': reportType = 'Financial Analysis'; break;
    }
    
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    // Create a form to submit export request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export_reports.php';
    
    const fields = {
        'export_type': 'excel',
        'report_type': reportType,
        'start_date': startDate,
        'end_date': endDate,
        'active_tab': activeTab.replace('#', '')
    };
    
    Object.keys(fields).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = fields[key];
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    Swal.fire({
        title: 'Exporting to Excel',
        text: 'Your report download will begin shortly',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>