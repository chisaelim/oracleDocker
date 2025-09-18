<?php
/**
 * Dashboard - Main page
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/utils.php';

$pageTitle = 'Dashboard';

// Get statistics
$stats = getDashboardStats();

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 mb-0">
            <i class="fas fa-tachometer-alt text-primary me-2"></i>
            Dashboard Overview
        </h1>
        <p class="text-muted mb-0">Welcome to the Oracle Business Administration System</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon mb-2">
                    <i class="fas fa-tags"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['client_types']) ?></h2>
                <p class="stats-label">Client Types</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <div class="card-body text-center">
                <div class="stats-icon mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['clients']) ?></h2>
                <p class="stats-label">Total Clients</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
            <div class="card-body text-center">
                <div class="stats-icon mb-2">
                    <i class="fas fa-box"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['products']) ?></h2>
                <p class="stats-label">Products</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
            <div class="card-body text-center">
                <div class="stats-icon mb-2">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h2 class="stats-number"><?= number_format($stats['employees']) ?></h2>
                <p class="stats-label">Employees</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="client_types.php" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-tags fa-2x mb-2 d-block"></i>
                            <strong>Manage Client Types</strong>
                            <br>
                            <small class="text-muted">Add, edit, or delete client types</small>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="clients.php" class="btn btn-outline-success w-100 p-3">
                            <i class="fas fa-users fa-2x mb-2 d-block"></i>
                            <strong>Manage Clients</strong>
                            <br>
                            <small class="text-muted">View and manage client information</small>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="products.php" class="btn btn-outline-warning w-100 p-3">
                            <i class="fas fa-box fa-2x mb-2 d-block"></i>
                            <strong>Manage Products</strong>
                            <br>
                            <small class="text-muted">Product catalog management</small>
                        </a>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <a href="employees.php" class="btn btn-outline-info w-100 p-3">
                            <i class="fas fa-user-tie fa-2x mb-2 d-block"></i>
                            <strong>Manage Employees</strong>
                            <br>
                            <small class="text-muted">Employee records and jobs</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Database Status -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Recent Client Types
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['recent_client_types'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type Name</th>
                                    <th>Discount Rate</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_client_types'] as $type): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($type['TYPE_NAME']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= formatPercentage($type['DISCOUNT_RATE']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($type['REMARKS'] ?? '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="client_types.php" class="btn btn-sm btn-outline-primary">
                            View All Client Types <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-2">No client types available</p>
                        <a href="client_types.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>
                            Add First Client Type
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-database me-2"></i>
                    Database Status
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <?php if ($stats['db_connected']): ?>
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0">Connection Status</h6>
                        <small class="text-muted">
                            <?= $stats['db_connected'] ? 'Connected to Oracle XE' : 'Database Offline' ?>
                        </small>
                    </div>
                </div>
                
                <?php if ($stats['db_connected']): ?>
                    <div class="mb-3">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-primary mb-0"><?= $stats['total_tables'] ?></h4>
                                    <small class="text-muted">Tables</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-success mb-0"><?= $stats['total_records'] ?></h4>
                                <small class="text-muted">Total Records</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-end">
                    <a href="database_info.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-info-circle me-1"></i>
                        More Details
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Application:</span>
                        <strong><?= APP_NAME ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>Version:</span>
                        <strong><?= APP_VERSION ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>PHP Version:</span>
                        <strong><?= phpversion() ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Server Time:</span>
                        <strong><?= date('Y-m-d H:i:s') ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';

// PHP Functions
function getDashboardStats() {
    $stats = [
        'client_types' => 0,
        'clients' => 0,
        'products' => 0,
        'employees' => 0,
        'recent_client_types' => [],
        'db_connected' => false,
        'total_tables' => 0,
        'total_records' => 0
    ];
    
    try {
        $db = Database::getInstance();
        $stats['db_connected'] = $db->isConnected();
        
        if ($stats['db_connected']) {
            // Count client types
            $stmt = $db->query("SELECT COUNT(*) as count FROM Client_Type");
            $result = $db->fetchOne($stmt);
            $stats['client_types'] = $result['COUNT'] ?? 0;
            
            // Count clients
            $stmt = $db->query("SELECT COUNT(*) as count FROM Clients");
            $result = $db->fetchOne($stmt);
            $stats['clients'] = $result['COUNT'] ?? 0;
            
            // Count products
            $stmt = $db->query("SELECT COUNT(*) as count FROM Products");
            $result = $db->fetchOne($stmt);
            $stats['products'] = $result['COUNT'] ?? 0;
            
            // Count employees
            $stmt = $db->query("SELECT COUNT(*) as count FROM Employees");
            $result = $db->fetchOne($stmt);
            $stats['employees'] = $result['COUNT'] ?? 0;
            
            // Get recent client types
            $stmt = $db->query("SELECT TYPE_NAME, DISCOUNT_RATE, REMARKS FROM Client_Type ORDER BY CLIENT_TYPE DESC FETCH FIRST 5 ROWS ONLY");
            $stats['recent_client_types'] = $db->fetchAll($stmt);
            
            // Count total tables
            $stmt = $db->query("SELECT COUNT(*) as count FROM user_tables WHERE table_name IN ('CLIENT_TYPE', 'CLIENTS', 'PRODUCT_TYPE', 'PRODUCTS', 'JOBS', 'EMPLOYEES', 'INVOICES', 'INVOICE_DETAILS')");
            $result = $db->fetchOne($stmt);
            $stats['total_tables'] = $result['COUNT'] ?? 0;
            
            // Calculate total records
            $totalRecords = $stats['client_types'] + $stats['clients'] + $stats['products'] + $stats['employees'];
            $stats['total_records'] = $totalRecords;
        }
        
    } catch (Exception $e) {
        logError("Error getting dashboard stats: " . $e->getMessage());
        // Stats will remain at default values
    }
    
    return $stats;
}
?>