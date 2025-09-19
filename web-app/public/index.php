<?php
/**
 * Enhanced Dashboard - Comprehensive Business Overview
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/utils.php';

$pageTitle = 'Dashboard';

// Get comprehensive statistics
$stats = getDashboardStats();

// Include header
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-tachometer-alt text-primary me-2"></i>
                    Business Intelligence Dashboard
                </h1>
                <p class="text-muted mb-0">Real-time overview of your business operations</p>
                <small class="text-muted">Last updated: <?= date('F j, Y \a\t g:i A') ?></small>
            </div>
            <div class="text-end">
                <?php if ($stats['db_connected']): ?>
                    <span class="badge bg-success fs-6">
                        <i class="fas fa-circle me-1"></i>System Online
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">
                        <i class="fas fa-exclamation-triangle me-1"></i>System Offline
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Statistics Cards Row 1 -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100" style="background: linear-gradient(135deg, #007bff, #0056b3);">
            <div class="card-body text-center text-white">
                <div class="stats-icon mb-2">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['clients']) ?></h2>
                <p class="stats-label mb-2">Total Clients</p>
                <small class="opacity-75">
                    <i class="fas fa-chart-line me-1"></i><?= $stats['active_clients'] ?> Active
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center text-white">
                <div class="stats-icon mb-2">
                    <i class="fas fa-file-invoice-dollar fa-2x"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['total_invoices']) ?></h2>
                <p class="stats-label mb-2">Total Invoices</p>
                <small class="opacity-75">
                    <i class="fas fa-dollar-sign me-1"></i><?= formatCurrency($stats['net_revenue']) ?> net
                    <?php if ($stats['total_discount_given'] > 0): ?>
                        <br><i class="fas fa-percentage me-1"></i><?= formatCurrency($stats['total_discount_given']) ?> discounts
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <div class="card-body text-center text-white">
                <div class="stats-icon mb-2">
                    <i class="fas fa-box fa-2x"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['products']) ?></h2>
                <p class="stats-label mb-2">Products</p>
                <small class="opacity-75">
                    <i class="fas fa-exclamation-triangle me-1"></i><?= $stats['low_stock_items'] ?> Low Stock
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
            <div class="card-body text-center text-white">
                <div class="stats-icon mb-2">
                    <i class="fas fa-user-tie fa-2x"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['employees']) ?></h2>
                <p class="stats-label mb-2">Employees</p>
                <small class="opacity-75">
                    <i class="fas fa-chart-bar me-1"></i><?= number_format($stats['avg_salary']) ?> Avg Salary
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Financial Overview Row -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
                <h4 class="text-success"><?= formatCurrency($stats['monthly_net_revenue'] ?? $stats['monthly_revenue']) ?></h4>
                <p class="card-text mb-0">This Month Revenue</p>
                <small class="text-muted">
                    <?php if ($stats['monthly_revenue'] == 0): ?>
                        No invoices this month
                    <?php else: ?>
                        <?php if (($stats['monthly_discount_given'] ?? 0) > 0): ?>
                            Net after <?= formatCurrency($stats['monthly_discount_given']) ?> discounts
                        <?php else: ?>
                            <?= $stats['revenue_growth'] >= 0 ? '+' : '' ?><?= number_format($stats['revenue_growth'], 1) ?>% vs last month
                        <?php endif; ?>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <div class="text-warning mb-2">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h4 class="text-warning"><?= $stats['pending_invoices'] ?></h4>
                <p class="card-text mb-0">Pending Invoices</p>
                <small class="text-muted">
                    <?= formatCurrency($stats['pending_amount']) ?> pending
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <div class="text-info mb-2">
                    <i class="fas fa-percentage fa-2x"></i>
                </div>
                <h4 class="text-info"><?= number_format($stats['avg_discount'], 1) ?>%</h4>
                <p class="card-text mb-0">Average Discount</p>
                <small class="text-muted">
                    <?= $stats['discount_clients'] ?>/<?= $stats['total_clients'] ?> clients (<?= number_format($stats['discount_percentage'], 1) ?>%)
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <div class="text-primary mb-2">
                    <i class="fas fa-star fa-2x"></i>
                </div>
                <h4 class="text-primary"><?= formatCurrency($stats['top_client_value']) ?></h4>
                <p class="card-text mb-0">Top Client Value</p>
                <small class="text-muted">
                    <?= htmlspecialchars($stats['top_client_name']) ?>
                </small>
            </div>
        </div>
    </div>
</div>


<!-- Main Dashboard Content -->
<div class="row mb-4">
    <!-- Recent Invoices -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-file-invoice me-2"></i>
                    Recent Invoices
                </h5>
                <span class="badge bg-primary"><?= $stats['total_invoices'] ?> Total</span>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['recent_invoices'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Discount</th>
                                    <th>Status</th>
                                    <th>Employee</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_invoices'] as $invoice): ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary">#<?= $invoice['INVOICENO'] ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-building me-2 text-muted"></i>
                                            <?= htmlspecialchars($invoice['CLIENTNAME']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y', strtotime($invoice['INVOICE_DATE'])) ?></small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong class="text-success"><?= formatCurrency($invoice['TOTAL_AMOUNT'] ?? 0) ?></strong>
                                            <?php if (($invoice['CLIENT_DISCOUNT'] ?? 0) > 0): ?>
                                                <br><small class="text-muted">Net: <?= formatCurrency($invoice['NET_AMOUNT'] ?? 0) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (($invoice['CLIENT_DISCOUNT'] ?? 0) > 0): ?>
                                            <span class="badge bg-success">
                                                <?= number_format($invoice['CLIENT_DISCOUNT'], 1) ?>%
                                            </span>
                                            <br><small class="text-success">-<?= formatCurrency($invoice['DISCOUNT_AMOUNT'] ?? 0) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($invoice['INVOICE_STATUS']) {
                                            'Delivered' => 'success',
                                            'Shipped' => 'info',
                                            'Pending' => 'warning',
                                            'Cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= $invoice['INVOICE_STATUS'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($invoice['EMPLOYEENAME']) ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="invoices.php" class="btn btn-outline-primary">
                            View All Invoices <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No invoices found</h6>
                        <p class="text-muted">Start creating invoices to see them here</p>
                        <a href="invoices.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create First Invoice
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Performing Items -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy me-2"></i>
                    Top Products
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['top_products'])): ?>
                    <?php foreach ($stats['top_products'] as $index => $product): ?>
                    <div class="d-flex align-items-center mb-3 <?= $index < count($stats['top_products']) - 1 ? 'border-bottom pb-3' : '' ?>">
                        <div class="flex-shrink-0 me-3">
                            <div class="badge bg-primary rounded-pill" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                <?= $index + 1 ?>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= htmlspecialchars($product['PRODUCTNAME']) ?></h6>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Sold: <?= $product['TOTAL_SOLD'] ?> units</small>
                                <small class="text-success fw-bold"><?= formatCurrency($product['TOTAL_REVENUE'] ?? 0) ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-end mt-3">
                        <a href="products.php" class="btn btn-sm btn-outline-primary">
                            View All Products <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No product data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Client Analysis & Inventory Status -->
<div class="row mb-4">
    <!-- Client Type Distribution -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Client Type Distribution
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['client_type_distribution'])): ?>
                    <?php foreach ($stats['client_type_distribution'] as $type): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-medium"><?= htmlspecialchars($type['TYPE_NAME']) ?></span>
                            <span class="text-muted"><?= $type['CLIENT_COUNT'] ?> clients</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?= min(100, max(0, $type['PERCENTAGE'] ?? 0)) ?>%;" 
                                 aria-valuenow="<?= $type['PERCENTAGE'] ?? 0 ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted"><?= number_format($type['PERCENTAGE'], 1) ?>% of total</small>
                            <small class="text-success">Avg: <?= number_format($type['DISCOUNT_RATE'], 1) ?>% discount</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-end mt-3">
                        <a href="client_types.php" class="btn btn-sm btn-outline-primary">
                            Manage Client Types <i class="fas fa-cog ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No client type data available</p>
                        <a href="client_types.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Add Client Types
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Discount Analysis -->
                <?php if ($stats['discount_clients'] > 0): ?>
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-tags me-2"></i>Discount Analysis
                        </h6>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="small">
                                    <div class="text-success fw-bold"><?= number_format($stats['min_discount'], 1) ?>%</div>
                                    <div class="text-muted">Min Discount</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="small">
                                    <div class="text-warning fw-bold"><?= number_format($stats['avg_discount'], 1) ?>%</div>
                                    <div class="text-muted">Avg Discount</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="small">
                                    <div class="text-danger fw-bold"><?= number_format($stats['max_discount'], 1) ?>%</div>
                                    <div class="text-muted">Max Discount</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar bg-info" style="width: <?= number_format($stats['discount_percentage'], 1) ?>%"></div>
                            </div>
                            <small class="text-muted">
                                <?= number_format($stats['discount_percentage'], 1) ?>% of clients receive discounts
                            </small>
                        </div>
                        
                        <!-- Financial Impact -->
                        <div class="mt-3 pt-2 border-top">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="small">
                                        <div class="text-danger fw-bold"><?= formatCurrency($stats['total_discount_impact']) ?></div>
                                        <div class="text-muted">Total Discounts</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="small">
                                        <div class="text-warning fw-bold"><?= number_format($stats['discount_impact_percentage'], 1) ?>%</div>
                                        <div class="text-muted">Revenue Impact</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Inventory Status -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-warehouse me-2"></i>
                    Inventory Status
                </h5>
            </div>
            <div class="card-body">
                <!-- Inventory Summary -->
                <div class="row text-center mb-4">
                    <div class="col-4">
                        <div class="border-end">
                            <h4 class="text-success mb-1"><?= $stats['in_stock_products'] ?></h4>
                            <small class="text-muted">In Stock</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h4 class="text-warning mb-1"><?= $stats['low_stock_items'] ?></h4>
                            <small class="text-muted">Low Stock</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <h4 class="text-danger mb-1"><?= $stats['out_of_stock'] ?></h4>
                        <small class="text-muted">Out of Stock</small>
                    </div>
                </div>
                
                <!-- Low Stock Alert -->
                <?php if (!empty($stats['low_stock_products'])): ?>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Low Stock Alert
                        </h6>
                        <div class="mb-0">
                            <?php foreach (array_slice($stats['low_stock_products'], 0, 3) as $product): ?>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><?= htmlspecialchars($product['PRODUCTNAME']) ?></span>
                                <span class="badge bg-warning text-dark"><?= $product['QTY_ON_HAND'] ?> left</span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($stats['low_stock_products']) > 3): ?>
                            <small class="text-muted">... and <?= count($stats['low_stock_products']) - 3 ?> more items</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Out Stock Alert -->
                <?php if (!empty($stats['out_of_stock_products'])): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Out of Stock Alert
                        </h6>
                        <div class="mb-0">
                            <?php foreach (array_slice($stats['out_of_stock_products'], 0, 3) as $product): ?>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><?= htmlspecialchars($product['PRODUCTNAME']) ?></span>
                                <span class="badge bg-danger text-dark"><?= $product['QTY_ON_HAND'] ?> left</span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (count($stats['out_of_stock_products']) > 3): ?>
                            <small class="text-muted">... and <?= count($stats['out_of_stock_products']) - 3 ?> more items</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-end">
                    <a href="products.php" class="btn btn-sm btn-outline-primary">
                        Manage Inventory <i class="fas fa-boxes ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Employee Performance & System Status -->
<div class="row mb-4">
    <!-- Employee Performance -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-users-cog me-2"></i>
                    Employee Performance
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['employee_performance'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Invoices</th>
                                    <th>Sales</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['employee_performance'] as $emp): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <small class="text-white fw-bold">
                                                    <?= strtoupper(substr($emp['EMPLOYEENAME'], 0, 2)) ?>
                                                </small>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?= htmlspecialchars($emp['EMPLOYEENAME']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($emp['JOB_TITLE']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= $emp['INVOICE_COUNT'] ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?= formatCurrency($emp['TOTAL_SALES'] ?? 0) ?></strong>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?= min(100, max(0, $emp['PERFORMANCE_PERCENT'] ?? 0)) ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= number_format($emp['PERFORMANCE_PERCENT'] ?? 0, 1) ?>%</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="employees.php" class="btn btn-sm btn-outline-primary">
                            View All Employees <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No employee performance data</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- System Status & Quick Actions -->
    <div class="col-lg-6 mb-4">
        <!-- Database Status -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-database me-2"></i>
                    System Status
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <?php if ($stats['db_connected']): ?>
                                <i class="fas fa-check-circle fa-2x text-success me-2"></i>
                                <div>
                                    <h6 class="mb-0 text-success">Online</h6>
                                    <small class="text-muted">Oracle XE</small>
                                </div>
                            <?php else: ?>
                                <i class="fas fa-times-circle fa-2x text-danger me-2"></i>
                                <div>
                                    <h6 class="mb-0 text-danger">Offline</h6>
                                    <small class="text-muted">Database</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border-start">
                            <h4 class="text-primary mb-0"><?= number_format($stats['total_records']) ?></h4>
                            <small class="text-muted">Total Records</small>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3 text-center">
                    <div class="col-4">
                        <div class="small">
                            <div class="text-muted">Tables</div>
                            <strong><?= $stats['total_tables'] ?></strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="small">
                            <div class="text-muted">PHP</div>
                            <strong><?= phpversion() ?></strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="small">
                            <div class="text-muted">Uptime</div>
                            <strong><?= date('H:i') ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-3">
                    <a href="database_info.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-info-circle me-1"></i>System Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="invoices.php" class="btn btn-outline-primary w-100 btn-sm">
                            <i class="fas fa-plus me-1"></i>New Invoice
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="clients.php" class="btn btn-outline-success w-100 btn-sm">
                            <i class="fas fa-user-plus me-1"></i>Add Client
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="products.php" class="btn btn-outline-warning w-100 btn-sm">
                            <i class="fas fa-box me-1"></i>Add Product
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="reports.php" class="btn btn-outline-info w-100 btn-sm">
                            <i class="fas fa-chart-bar me-1"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';

// Enhanced PHP Functions for Dashboard
function getDashboardStats() {
    $stats = [
        // Basic counts
        'client_types' => 0,
        'clients' => 0,
        'products' => 0,
        'employees' => 0,
        'total_invoices' => 0,
        'db_connected' => false,
        'total_tables' => 0,
        'total_records' => 0,
        
        // Enhanced metrics
        'active_clients' => 0,
        'total_revenue' => 0,
        'total_discount_given' => 0,
        'net_revenue' => 0,
        'monthly_revenue' => 0,
        'monthly_discount_given' => 0,
        'monthly_net_revenue' => 0,
        'revenue_growth' => 0,
        'pending_invoices' => 0,
        'pending_amount' => 0,
        'low_stock_items' => 0,
        'out_of_stock' => 0,
        'in_stock_products' => 0,
        'avg_discount' => 0,
        'avg_discount_all' => 0,
        'discount_clients' => 0,
        'total_clients' => 0,
        'max_discount' => 0,
        'min_discount' => 0,
        'discount_percentage' => 0,
        'total_discount_impact' => 0,
        'total_gross_revenue' => 0,
        'total_net_revenue' => 0,
        'discount_impact_percentage' => 0,
        'avg_salary' => 0,
        'top_client_value' => 0,
        'top_client_name' => 'N/A',
        
        // Data arrays
        'recent_invoices' => [],
        'top_products' => [],
        'client_type_distribution' => [],
        'low_stock_products' => [],
        'employee_performance' => []
    ];
    
    try {
        $db = Database::getInstance();
        $stats['db_connected'] = $db->isConnected();
        
        if ($stats['db_connected']) {
            // Basic counts
            $stmt = $db->query("SELECT COUNT(*) as count FROM Client_Type");
            $result = $db->fetchOne($stmt);
            $stats['client_types'] = $result['COUNT'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM Clients");
            $result = $db->fetchOne($stmt);
            $stats['clients'] = $result['COUNT'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM Products");
            $result = $db->fetchOne($stmt);
            $stats['products'] = $result['COUNT'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM Employees");
            $result = $db->fetchOne($stmt);
            $stats['employees'] = $result['COUNT'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM Invoices");
            $result = $db->fetchOne($stmt);
            $stats['total_invoices'] = $result['COUNT'] ?? 0;
            
            // Active clients (clients with invoices)
            $stmt = $db->query("SELECT COUNT(DISTINCT CLIENT_NO) as count FROM Invoices");
            $result = $db->fetchOne($stmt);
            $stats['active_clients'] = $result['COUNT'] ?? 0;
            
            // Financial metrics with discount impact
            $stmt = $db->query("
                SELECT 
                    SUM(id.QTY * id.PRICE) as total_revenue,
                    SUM(CASE 
                        WHEN c.DISCOUNT > 0 THEN (id.QTY * id.PRICE) * (c.DISCOUNT / 100)
                        ELSE 0 
                    END) as total_discount_given,
                    SUM(CASE 
                        WHEN c.DISCOUNT > 0 THEN (id.QTY * id.PRICE) * (1 - c.DISCOUNT / 100)
                        ELSE (id.QTY * id.PRICE) 
                    END) as net_revenue
                FROM Invoice_Details id
                JOIN Invoices i ON id.INVOICENO = i.INVOICENO
                JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                WHERE i.INVOICE_STATUS != 'Cancelled'
            ");
            $result = $db->fetchOne($stmt);
            $stats['total_revenue'] = $result['TOTAL_REVENUE'] ?? 0;
            $stats['total_discount_given'] = $result['TOTAL_DISCOUNT_GIVEN'] ?? 0;
            $stats['net_revenue'] = $result['NET_REVENUE'] ?? 0;
            
            // Monthly revenue (current month) with discount impact
            $stmt = $db->query("
                SELECT 
                    SUM(id.QTY * id.PRICE) as monthly_revenue,
                    SUM(CASE 
                        WHEN c.DISCOUNT > 0 THEN (id.QTY * id.PRICE) * (c.DISCOUNT / 100)
                        ELSE 0 
                    END) as monthly_discount_given,
                    SUM(CASE 
                        WHEN c.DISCOUNT > 0 THEN (id.QTY * id.PRICE) * (1 - c.DISCOUNT / 100)
                        ELSE (id.QTY * id.PRICE) 
                    END) as monthly_net_revenue
                FROM Invoice_Details id
                JOIN Invoices i ON id.INVOICENO = i.INVOICENO
                JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                WHERE i.INVOICE_STATUS != 'Cancelled'
                AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
            ");
            $result = $db->fetchOne($stmt);
            $stats['monthly_revenue'] = $result['MONTHLY_REVENUE'] ?? 0;
            $stats['monthly_discount_given'] = $result['MONTHLY_DISCOUNT_GIVEN'] ?? 0;
            $stats['monthly_net_revenue'] = $result['MONTHLY_NET_REVENUE'] ?? 0;
            
            // Revenue growth (current month vs previous month) with discount impact
            $stmt = $db->query("
                SELECT 
                    SUM(id.QTY * id.PRICE) as previous_month_revenue,
                    SUM(CASE 
                        WHEN c.DISCOUNT > 0 THEN (id.QTY * id.PRICE) * (1 - c.DISCOUNT / 100)
                        ELSE (id.QTY * id.PRICE) 
                    END) as previous_month_net_revenue
                FROM Invoice_Details id
                JOIN Invoices i ON id.INVOICENO = i.INVOICENO
                JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                WHERE i.INVOICE_STATUS != 'Cancelled'
                AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM ADD_MONTHS(SYSDATE, -1))
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM ADD_MONTHS(SYSDATE, -1))
            ");
            $result = $db->fetchOne($stmt);
            $previous_month_revenue = $result['PREVIOUS_MONTH_REVENUE'] ?? 0;
            $previous_month_net_revenue = $result['PREVIOUS_MONTH_NET_REVENUE'] ?? 0;
            
            // Calculate growth percentage using net revenue
            $current_net = $stats['monthly_net_revenue'] ?? 0;
            if ($previous_month_net_revenue > 0) {
                $stats['revenue_growth'] = (($current_net - $previous_month_net_revenue) / $previous_month_net_revenue) * 100;
            } else {
                $stats['revenue_growth'] = $current_net > 0 ? 100 : 0; // 100% growth if previous was 0
            }
            
            // Pending invoices (current month only)
            $stmt = $db->query("
                SELECT COUNT(*) as count 
                FROM Invoices 
                WHERE INVOICE_STATUS = 'Pending'
                AND EXTRACT(MONTH FROM INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
            ");
            $result = $db->fetchOne($stmt);
            $stats['pending_invoices'] = $result['COUNT'] ?? 0;
            
            $stmt = $db->query("
                SELECT 
                    SUM(id.QTY * id.PRICE) as pending_amount
                FROM Invoice_Details id
                JOIN Invoices i ON id.INVOICENO = i.INVOICENO
                WHERE i.INVOICE_STATUS = 'Pending'
                AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
            ");
            $result = $db->fetchOne($stmt);
            $stats['pending_amount'] = $result['PENDING_AMOUNT'] ?? 0;
            
            // Inventory status
            $stmt = $db->query("SELECT COUNT(*) as count FROM Products WHERE QTY_ON_HAND <= REORDER_LEVEL AND QTY_ON_HAND > 0");
            $result = $db->fetchOne($stmt);
            $stats['low_stock_items'] = $result['COUNT'] ?? 0;
            
            $stmt = $db->query("SELECT COUNT(*) as count FROM Products WHERE QTY_ON_HAND <= 0");
            $result = $db->fetchOne($stmt);
            $stats['out_of_stock'] = $result['COUNT'] ?? 0;
            
            $stats['in_stock_products'] = $stats['products'] - $stats['out_of_stock'];
            
            // Enhanced discount calculations
            $stmt = $db->query("
                SELECT 
                    AVG(CASE WHEN DISCOUNT > 0 THEN DISCOUNT ELSE 0 END) as avg_discount_all,
                    AVG(DISCOUNT) as avg_discount_eligible,
                    COUNT(*) as total_clients,
                    COUNT(CASE WHEN DISCOUNT > 0 THEN 1 END) as discount_clients,
                    SUM(CASE WHEN DISCOUNT > 0 THEN DISCOUNT ELSE 0 END) as total_discount_amount,
                    MAX(DISCOUNT) as max_discount,
                    MIN(CASE WHEN DISCOUNT > 0 THEN DISCOUNT END) as min_discount
                FROM Clients
            ");
            $result = $db->fetchOne($stmt);
            $stats['avg_discount'] = $result['AVG_DISCOUNT_ELIGIBLE'] ?? 0; // Average of clients who have discount
            $stats['avg_discount_all'] = $result['AVG_DISCOUNT_ALL'] ?? 0; // Average across all clients
            $stats['discount_clients'] = $result['DISCOUNT_CLIENTS'] ?? 0;
            $stats['total_clients'] = $result['TOTAL_CLIENTS'] ?? 0;
            $stats['max_discount'] = $result['MAX_DISCOUNT'] ?? 0;
            $stats['min_discount'] = $result['MIN_DISCOUNT'] ?? 0;
            $stats['discount_percentage'] = $stats['total_clients'] > 0 ? ($stats['discount_clients'] / $stats['total_clients']) * 100 : 0;
            
            // Calculate current month discount impact on revenue
            $stmt = $db->query("
                SELECT 
                    SUM(id.QTY * id.PRICE) as total_gross_revenue,
                    SUM(
                        CASE 
                            WHEN c.DISCOUNT > 0 THEN id.QTY * id.PRICE * (c.DISCOUNT / 100)
                            ELSE 0 
                        END
                    ) as total_discount_amount,
                    SUM(
                        CASE 
                            WHEN c.DISCOUNT > 0 THEN id.QTY * id.PRICE * (1 - c.DISCOUNT / 100)
                            ELSE id.QTY * id.PRICE 
                        END
                    ) as total_net_revenue
                FROM Invoices i
                JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                JOIN Invoice_Details id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_STATUS != 'Cancelled'
                AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
            ");
            $result = $db->fetchOne($stmt);
            $stats['total_discount_impact'] = $result['TOTAL_DISCOUNT_AMOUNT'] ?? 0;
            $stats['total_gross_revenue'] = $result['TOTAL_GROSS_REVENUE'] ?? 0;
            $stats['total_net_revenue'] = $result['TOTAL_NET_REVENUE'] ?? 0;
            $stats['discount_impact_percentage'] = $stats['total_gross_revenue'] > 0 ? 
                ($stats['total_discount_impact'] / $stats['total_gross_revenue']) * 100 : 0;
            
            // Average salary
            $stmt = $db->query("SELECT AVG(SALARY) as avg_salary FROM Employees");
            $result = $db->fetchOne($stmt);
            $stats['avg_salary'] = $result['AVG_SALARY'] ?? 0;
            
            // Top client value (current month only)
            $stmt = $db->query("
                SELECT 
                    c.CLIENTNAME,
                    SUM(id.QTY * id.PRICE) as total_value
                FROM Clients c
                JOIN Invoices i ON c.CLIENT_NO = i.CLIENT_NO
                JOIN Invoice_Details id ON i.INVOICENO = id.INVOICENO
                WHERE i.INVOICE_STATUS != 'Cancelled'
                AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
                GROUP BY c.CLIENTNAME
                ORDER BY total_value DESC
                FETCH FIRST 1 ROWS ONLY
            ");
            $result = $db->fetchOne($stmt);
            if ($result) {
                $stats['top_client_value'] = $result['TOTAL_VALUE'];
                $stats['top_client_name'] = $result['CLIENTNAME'];
            }
            
            // Recent invoices with client discount information (current month only)
            $stmt = $db->query("
                SELECT 
                    i.INVOICENO,
                    i.INVOICE_DATE,
                    i.INVOICE_STATUS,
                    c.CLIENTNAME,
                    c.DISCOUNT as CLIENT_DISCOUNT,
                    e.EMPLOYEENAME,
                    SUM(id.QTY * id.PRICE) as TOTAL_AMOUNT,
                    CASE 
                        WHEN c.DISCOUNT > 0 THEN SUM(id.QTY * id.PRICE) * (c.DISCOUNT / 100)
                        ELSE 0 
                    END as DISCOUNT_AMOUNT,
                    CASE 
                        WHEN c.DISCOUNT > 0 THEN SUM(id.QTY * id.PRICE) * (1 - c.DISCOUNT / 100)
                        ELSE SUM(id.QTY * id.PRICE) 
                    END as NET_AMOUNT
                FROM Invoices i
                JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                LEFT JOIN Invoice_Details id ON i.INVOICENO = id.INVOICENO
                WHERE EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
                GROUP BY i.INVOICENO, i.INVOICE_DATE, i.INVOICE_STATUS, c.CLIENTNAME, c.DISCOUNT, e.EMPLOYEENAME
                ORDER BY i.INVOICE_DATE DESC
                FETCH FIRST 10 ROWS ONLY
            ");
            $stats['recent_invoices'] = $db->fetchAll($stmt);
            
            // Top products (current month only)
            $stmt = $db->query("
                SELECT 
                    p.PRODUCTNAME,
                    SUM(id.QTY) as TOTAL_SOLD,
                    SUM(id.QTY * id.PRICE) as TOTAL_REVENUE
                FROM Products p
                JOIN Invoice_Details id ON p.PRODUCT_NO = id.PRODUCT_NO
                JOIN Invoices i ON id.INVOICENO = i.INVOICENO
                WHERE i.INVOICE_STATUS != 'Cancelled'
                AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
                GROUP BY p.PRODUCTNAME
                ORDER BY TOTAL_REVENUE DESC
                FETCH FIRST 5 ROWS ONLY
            ");
            $stats['top_products'] = $db->fetchAll($stmt);
            
            // Client type distribution
            $stmt = $db->query("
                SELECT 
                    ct.TYPE_NAME,
                    ct.DISCOUNT_RATE,
                    COUNT(c.CLIENT_NO) as CLIENT_COUNT,
                    ROUND((COUNT(c.CLIENT_NO) * 100.0 / (SELECT COUNT(*) FROM Clients)), 1) as PERCENTAGE
                FROM Client_Type ct
                LEFT JOIN Clients c ON ct.CLIENT_TYPE = c.CLIENT_TYPE
                GROUP BY ct.TYPE_NAME, ct.DISCOUNT_RATE
                ORDER BY CLIENT_COUNT DESC
            ");
            $stats['client_type_distribution'] = $db->fetchAll($stmt);
            
            // Low stock products
            $stmt = $db->query("
                SELECT PRODUCTNAME, QTY_ON_HAND, REORDER_LEVEL
                FROM Products 
                WHERE QTY_ON_HAND <= REORDER_LEVEL AND QTY_ON_HAND > 0
                ORDER BY QTY_ON_HAND ASC
                FETCH FIRST 10 ROWS ONLY
            ");
            $stats['low_stock_products'] = $db->fetchAll($stmt);

            // out of stock products
            $stmt = $db->query("
                SELECT PRODUCTNAME, QTY_ON_HAND, REORDER_LEVEL
                FROM Products 
                WHERE QTY_ON_HAND <= 0
                ORDER BY QTY_ON_HAND ASC
                FETCH FIRST 10 ROWS ONLY
            ");
            $stats['out_of_stock_products'] = $db->fetchAll($stmt);
            
            // Employee performance (current month only)
            $stmt = $db->query("
                SELECT 
                    e.EMPLOYEENAME,
                    j.JOB_TITLE,
                    COUNT(i.INVOICENO) as INVOICE_COUNT,
                    NVL(SUM(id.QTY * id.PRICE), 0) as TOTAL_SALES,
                    CASE 
                        WHEN MAX(sales_max.max_sales) > 0 
                        THEN ROUND((NVL(SUM(id.QTY * id.PRICE), 0) * 100.0 / MAX(sales_max.max_sales)), 1)
                        ELSE 0 
                    END as PERFORMANCE_PERCENT
                FROM Employees e
                JOIN Jobs j ON e.JOB_ID = j.JOB_ID
                LEFT JOIN Invoices i ON e.EMPLOYEEID = i.EMPLOYEEID 
                    AND i.INVOICE_STATUS != 'Cancelled'
                    AND EXTRACT(MONTH FROM i.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                    AND EXTRACT(YEAR FROM i.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
                LEFT JOIN Invoice_Details id ON i.INVOICENO = id.INVOICENO
                CROSS JOIN (
                    SELECT MAX(emp_sales.sales) as max_sales
                    FROM (
                        SELECT NVL(SUM(id2.QTY * id2.PRICE), 0) as sales
                        FROM Employees e2
                        LEFT JOIN Invoices i2 ON e2.EMPLOYEEID = i2.EMPLOYEEID 
                            AND i2.INVOICE_STATUS != 'Cancelled'
                            AND EXTRACT(MONTH FROM i2.INVOICE_DATE) = EXTRACT(MONTH FROM SYSDATE)
                            AND EXTRACT(YEAR FROM i2.INVOICE_DATE) = EXTRACT(YEAR FROM SYSDATE)
                        LEFT JOIN Invoice_Details id2 ON i2.INVOICENO = id2.INVOICENO
                        GROUP BY e2.EMPLOYEEID
                    ) emp_sales
                ) sales_max
                GROUP BY e.EMPLOYEENAME, j.JOB_TITLE
                ORDER BY TOTAL_SALES DESC
            ");
            $stats['employee_performance'] = $db->fetchAll($stmt);
            
            // System tables count
            $stmt = $db->query("SELECT COUNT(*) as count FROM user_tables WHERE table_name IN ('CLIENT_TYPE', 'CLIENTS', 'PRODUCT_TYPE', 'PRODUCTS', 'JOBS', 'EMPLOYEES', 'INVOICES', 'INVOICE_DETAILS')");
            $result = $db->fetchOne($stmt);
            $stats['total_tables'] = $result['COUNT'] ?? 0;
            
            // Calculate total records across all main tables
            $tables = ['Client_Type', 'Clients', 'Products', 'Employees', 'Invoices', 'Invoice_Details'];
            $totalRecords = 0;
            foreach ($tables as $table) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
                    $result = $db->fetchOne($stmt);
                    $totalRecords += $result['COUNT'] ?? 0;
                } catch (Exception $e) {
                    // Skip if table doesn't exist
                }
            }
            $stats['total_records'] = $totalRecords;
        }
        
    } catch (Exception $e) {
        logError("Error getting dashboard stats: " . $e->getMessage());
        // Stats will remain at default values
    }
    
    return $stats;
}
?>