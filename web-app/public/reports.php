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
        
        // Summary data
        $sql = "SELECT 
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    COUNT(DISTINCT i.INVOICENO) as total_invoices,
                    COUNT(DISTINCT i.CLIENT_NO) as active_clients
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $summary = $db->fetchOne($stmt);
        
        // Chart data - daily sales
        $sql = "SELECT 
                    TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD') as sale_date,
                    NVL(SUM(id.QTY * id.PRICE), 0) as daily_total
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD')
                ORDER BY sale_date";
        
        $stmt = $db->query($sql, $params);
        $chart_raw = $db->fetchAll($stmt);
        
        $chart_data = [
            'labels' => array_column($chart_raw, 'SALE_DATE'),
            'values' => array_map('floatval', array_column($chart_raw, 'DAILY_TOTAL'))
        ];
        
        // Status distribution
        $sql = "SELECT 
                    i.INVOICE_STATUS,
                    COUNT(*) as status_count
                FROM INVOICES i
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY i.INVOICE_STATUS";
        
        $stmt = $db->query($sql, $params);
        $status_raw = $db->fetchAll($stmt);
        
        $status_data = [
            'labels' => array_column($status_raw, 'INVOICE_STATUS'),
            'values' => array_map('intval', array_column($status_raw, 'STATUS_COUNT'))
        ];
        
        // Detailed data
        $sql = "SELECT 
                    TO_CHAR(i.INVOICE_DATE, 'YYYY-MM-DD') as invoice_date,
                    i.INVOICENO as invoice_no,
                    c.CLIENTNAME as client_name,
                    e.EMPLOYEENAME as employee_name,
                    i.INVOICE_STATUS as status,
                    NVL(SUM(id.QTY * id.PRICE), 0) as amount
                FROM INVOICES i
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY i.INVOICENO, i.INVOICE_DATE, c.CLIENTNAME, e.EMPLOYEENAME, i.INVOICE_STATUS
                ORDER BY i.INVOICE_DATE DESC";
        
        $stmt = $db->query($sql, $params);
        $detailed_data = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'chart_data' => $chart_data,
            'status_data' => $status_data,
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
        
        // Stock levels for chart
        $sql = "SELECT 
                    PRODUCTNAME,
                    QTY_ON_HAND as stock_level,
                    REORDER_LEVEL as reorder_level
                FROM Products
                ORDER BY PRODUCTNAME";
        
        $stmt = $db->query($sql);
        $stock_raw = $db->fetchAll($stmt);
        
        $stock_data = [
            'labels' => array_column($stock_raw, 'PRODUCTNAME'),
            'stock_levels' => array_map('intval', array_column($stock_raw, 'STOCK_LEVEL')),
            'reorder_levels' => array_map('intval', array_column($stock_raw, 'REORDER_LEVEL'))
        ];
        
        // Low stock items
        $sql = "SELECT 
                    PRODUCTNAME as product_name,
                    QTY_ON_HAND as stock_level,
                    REORDER_LEVEL as reorder_level
                FROM Products
                WHERE QTY_ON_HAND <= REORDER_LEVEL
                ORDER BY QTY_ON_HAND ASC";
        
        $stmt = $db->query($sql);
        $low_stock = $db->fetchAll($stmt);
        
        // Product performance
        $sql = "SELECT 
                    p.PRODUCTNAME as product_name,
                    pt.PRODUCTTYPE_NAME as category,
                    p.QTY_ON_HAND as stock_level,
                    p.REORDER_LEVEL as reorder_level,
                    NVL(SUM(id.QTY), 0) as units_sold,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue
                FROM Products p
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                LEFT JOIN INVOICE_DETAILS id ON p.PRODUCT_NO = id.PRODUCT_NO
                GROUP BY p.PRODUCTNAME, pt.PRODUCTTYPE_NAME, p.QTY_ON_HAND, p.REORDER_LEVEL
                ORDER BY revenue DESC";
        
        $stmt = $db->query($sql);
        $product_performance = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'stock_data' => $stock_data,
            'low_stock' => $low_stock,
            'low_stock_count' => count($low_stock),
            'product_performance' => $product_performance
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
        
        // Top clients by revenue
        $sql = "SELECT 
                    c.CLIENTNAME as client_name,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue
                FROM Clients c
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY c.CLIENTNAME
                ORDER BY revenue DESC
                FETCH FIRST 10 ROWS ONLY";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $top_clients_raw = $db->fetchAll($stmt);
        
        $top_clients = [
            'labels' => array_column($top_clients_raw, 'CLIENT_NAME'),
            'values' => array_map('floatval', array_column($top_clients_raw, 'REVENUE'))
        ];
        
        // Client types distribution
        $sql = "SELECT 
                    ct.TYPE_NAME as client_type,
                    COUNT(c.CLIENT_NO) as client_count
                FROM Client_Type ct
                LEFT JOIN Clients c ON ct.CLIENT_TYPE = c.CLIENT_TYPE
                GROUP BY ct.TYPE_NAME";
        
        $stmt = $db->query($sql);
        $client_types_raw = $db->fetchAll($stmt);
        
        $client_types = [
            'labels' => array_column($client_types_raw, 'CLIENT_TYPE'),
            'values' => array_map('intval', array_column($client_types_raw, 'CLIENT_COUNT'))
        ];
        
        // Detailed client data
        $sql = "SELECT 
                    c.CLIENTNAME as client_name,
                    ct.TYPE_NAME as client_type,
                    COUNT(i.INVOICENO) as total_orders,
                    NVL(SUM(id.QTY * id.PRICE), 0) as total_revenue,
                    MAX(i.INVOICE_DATE) as last_order,
                    CASE 
                        WHEN MAX(i.INVOICE_DATE) >= SYSDATE - 30 THEN 'Active'
                        ELSE 'Inactive'
                    END as status
                FROM Clients c
                LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
                LEFT JOIN INVOICES i ON c.CLIENT_NO = i.CLIENT_NO
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY c.CLIENTNAME, ct.TYPE_NAME
                ORDER BY total_revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $client_details = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'top_clients' => $top_clients,
            'client_types' => $client_types,
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
        
        // Employee performance for chart
        $sql = "SELECT 
                    e.EMPLOYEENAME as employee_name,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue
                FROM Employees e
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY e.EMPLOYEENAME
                ORDER BY revenue DESC";
        
        $params = [':start_date' => $start_date, ':end_date' => $end_date];
        $stmt = $db->query($sql, $params);
        $performance_raw = $db->fetchAll($stmt);
        
        $performance_data = [
            'labels' => array_column($performance_raw, 'EMPLOYEE_NAME'),
            'values' => array_map('floatval', array_column($performance_raw, 'REVENUE'))
        ];
        
        // Top 3 performers
        $top_performers = array_slice($performance_raw, 0, 3);
        foreach ($top_performers as &$performer) {
            // Get job title
            $sql = "SELECT j.JOB_TITLE FROM Employees e 
                    JOIN JOBS j ON e.JOB_ID = j.JOB_ID 
                    WHERE e.EMPLOYEENAME = :name";
            $stmt = $db->query($sql, [':name' => $performer['EMPLOYEE_NAME']]);
            $job = $db->fetchOne($stmt);
            $performer['job_title'] = $job['JOB_TITLE'] ?? 'N/A';
            
            // Get total sales count
            $sql = "SELECT COUNT(*) as total_sales FROM INVOICES i 
                    JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID 
                    WHERE e.EMPLOYEENAME = :name 
                    AND i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                    AND TO_DATE(:end_date, 'YYYY-MM-DD')";
            $stmt = $db->query($sql, [
                ':name' => $performer['EMPLOYEE_NAME'],
                ':start_date' => $start_date,
                ':end_date' => $end_date
            ]);
            $sales = $db->fetchOne($stmt);
            $performer['total_sales'] = $sales['TOTAL_SALES'] ?? 0;
        }
        
        // Detailed employee data
        $sql = "SELECT 
                    e.EMPLOYEENAME as employee_name,
                    j.JOB_TITLE as job_title,
                    COUNT(i.INVOICENO) as total_sales,
                    NVL(SUM(id.QTY * id.PRICE), 0) as revenue,
                    CASE 
                        WHEN COUNT(i.INVOICENO) > 0 
                        THEN NVL(SUM(id.QTY * id.PRICE), 0) / COUNT(i.INVOICENO)
                        ELSE 0 
                    END as avg_order_value,
                    CASE 
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 10000 THEN 'Excellent'
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 5000 THEN 'Good'
                        WHEN NVL(SUM(id.QTY * id.PRICE), 0) > 1000 THEN 'Average'
                        ELSE 'Poor'
                    END as performance
                FROM Employees e
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
                LEFT JOIN INVOICES i ON e.EMPLOYEEID = i.EMPLOYEEID
                LEFT JOIN INVOICE_DETAILS id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_DATE BETWEEN TO_DATE(:start_date, 'YYYY-MM-DD') 
                      AND TO_DATE(:end_date, 'YYYY-MM-DD')
                GROUP BY e.EMPLOYEENAME, j.JOB_TITLE
                ORDER BY revenue DESC";
        
        $stmt = $db->query($sql, $params);
        $detailed_data = $db->fetchAll($stmt);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'performance_data' => $performance_data,
            'top_performers' => $top_performers,
            'detailed_data' => $detailed_data
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
        case 'monthly_sales':
            getSalesReportData();
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
            if (data.success) {
                document.getElementById('total-revenue').textContent = '$' + (data.summary.total_revenue || 0).toLocaleString();
                document.getElementById('total-invoices').textContent = data.summary.total_invoices || 0;
                document.getElementById('active-clients').textContent = data.summary.active_clients || 0;
            }
        })
        .catch(error => console.error('Error loading summary:', error));
        
    fetch(`reports.php?action=inventory_report`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('low-stock').textContent = data.low_stock_count || 0;
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
                updateSalesChart(data.chart_data);
                updateStatusChart(data.status_data);
                updateSalesTable(data.detailed_data);
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
                updateStockChart(data.stock_data);
                updateLowStockAlerts(data.low_stock);
                updateInventoryTable(data.product_performance);
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
                updateClientChart(data.top_clients);
                updateClientTypeChart(data.client_types);
                updateClientTable(data.client_details);
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
                updateEmployeeChart(data.performance_data);
                updateTopPerformers(data.top_performers);
                updateEmployeeTable(data.detailed_data);
            }
        })
        .catch(error => console.error('Error loading employee report:', error));
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
                label: 'Daily Sales',
                data: data.values,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
                data: data.values,
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
            maintainAspectRatio: false
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
                data: data.values,
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
            maintainAspectRatio: false
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
                data: data.values,
                backgroundColor: 'rgba(153, 102, 255, 0.8)'
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
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${new Date(row.invoice_date).toLocaleDateString()}</td>
            <td>${row.invoice_no}</td>
            <td>${row.client_name}</td>
            <td>${row.employee_name}</td>
            <td><span class="badge bg-${getStatusColor(row.status)}">${row.status}</span></td>
            <td>$${parseFloat(row.amount).toLocaleString()}</td>
        `;
        tbody.appendChild(tr);
    });
}

function updateInventoryTable(data) {
    const tbody = document.getElementById('inventoryTableBody');
    tbody.innerHTML = '';
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.product_name}</td>
            <td>${row.category}</td>
            <td>${row.stock_level}</td>
            <td>${row.reorder_level}</td>
            <td>${row.units_sold}</td>
            <td>$${parseFloat(row.revenue).toLocaleString()}</td>
        `;
        tbody.appendChild(tr);
    });
}

function updateClientTable(data) {
    const tbody = document.getElementById('clientTableBody');
    tbody.innerHTML = '';
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.client_name}</td>
            <td>${row.client_type}</td>
            <td>${row.total_orders}</td>
            <td>$${parseFloat(row.total_revenue).toLocaleString()}</td>
            <td>${row.last_order ? new Date(row.last_order).toLocaleDateString() : 'N/A'}</td>
            <td><span class="badge bg-${row.status === 'Active' ? 'success' : 'secondary'}">${row.status}</span></td>
        `;
        tbody.appendChild(tr);
    });
}

function updateEmployeeTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    tbody.innerHTML = '';
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.employee_name}</td>
            <td>${row.job_title}</td>
            <td>${row.total_sales}</td>
            <td>$${parseFloat(row.revenue).toLocaleString()}</td>
            <td>$${parseFloat(row.avg_order_value).toLocaleString()}</td>
            <td><span class="badge bg-${getPerformanceColor(row.performance)}">${row.performance}</span></td>
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
        alert.className = 'alert alert-warning d-flex justify-content-between align-items-center mb-2';
        alert.innerHTML = `
            <div>
                <strong>${item.product_name}</strong><br>
                <small>Stock: ${item.stock_level} | Reorder at: ${item.reorder_level}</small>
            </div>
            <span class="badge bg-warning">${item.stock_level}</span>
        `;
        container.appendChild(alert);
    });
}

function updateTopPerformers(data) {
    const container = document.getElementById('topPerformers');
    container.innerHTML = '';
    
    data.forEach((performer, index) => {
        const item = document.createElement('div');
        item.className = 'd-flex justify-content-between align-items-center mb-3';
        item.innerHTML = `
            <div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-${index === 0 ? 'warning' : index === 1 ? 'secondary' : 'dark'} me-2">
                        ${index + 1}
                    </span>
                    <strong>${performer.employee_name}</strong>
                </div>
                <small class="text-muted">${performer.job_title}</small>
            </div>
            <div class="text-end">
                <div><strong>$${parseFloat(performer.revenue).toLocaleString()}</strong></div>
                <small class="text-muted">${performer.total_sales} sales</small>
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

// Export functions
function exportToPDF() {
    const activeTab = document.querySelector('#reportTabs .nav-link.active').getAttribute('data-bs-target');
    let reportType = 'All Reports';
    
    switch(activeTab) {
        case '#sales': reportType = 'Sales Report'; break;
        case '#inventory': reportType = 'Inventory Report'; break;
        case '#clients': reportType = 'Client Report'; break;
        case '#employees': reportType = 'Employee Report'; break;
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