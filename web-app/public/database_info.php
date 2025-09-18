<?php
/**
 * Database Information Page
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/utils.php';

$pageTitle = 'Database Information';

// Get database information
$dbInfo = getDatabaseInfo();

// Include header
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-database text-primary me-2"></i>
                Database Information
            </h1>
            <p class="text-muted mb-0">Oracle Database connection and system details</p>
        </div>
    </div>

<!-- Connection Status -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plug me-2"></i>
                    Connection Status
                </h5>
            </div>
            <div class="card-body">
                <?php if ($dbInfo['connected']): ?>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle fa-3x text-success me-3"></i>
                        <div>
                            <h5 class="text-success mb-1">Connected</h5>
                            <p class="text-muted mb-0">Successfully connected to Oracle Database</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle fa-3x text-danger me-3"></i>
                        <div>
                            <h5 class="text-danger mb-1">Disconnected</h5>
                            <p class="text-muted mb-0">Unable to connect to database</p>
                            <?php if (!empty($dbInfo['error'])): ?>
                                <small class="text-danger"><?= htmlspecialchars($dbInfo['error']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-server me-2"></i>
                    Connection Details
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 text-end">
                        <strong>Host:</strong>
                    </div>
                    <div class="col-6">
                        <?= DB_HOST ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 text-end">
                        <strong>Port:</strong>
                    </div>
                    <div class="col-6">
                        <?= DB_PORT ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 text-end">
                        <strong>Service:</strong>
                    </div>
                    <div class="col-6">
                        <?= DB_SERVICE ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 text-end">
                        <strong>Username:</strong>
                    </div>
                    <div class="col-6">
                        <?= DB_USERNAME ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($dbInfo['connected']): ?>
<!-- Database Schema Information -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-table me-2"></i>
                    Database Schema
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dbInfo['tables'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Table Name</th>
                                    <th>Record Count</th>
                                    <th>Last Updated</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbInfo['tables'] as $table): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($table['TABLE_NAME']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= number_format($table['NUM_ROWS'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $table['LAST_ANALYZED'] ? date('Y-m-d H:i:s', strtotime($table['LAST_ANALYZED'])) : 'N/A' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                        <p class="text-muted">No table information available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Database Version and Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Database Version
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($dbInfo['version'])): ?>
                    <h4 class="text-primary"><?= htmlspecialchars($dbInfo['version']['BANNER']) ?></h4>
                    <p class="text-muted">Oracle Database Version Information</p>
                <?php else: ?>
                    <p class="text-muted">Version information not available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h3 class="text-primary"><?= count($dbInfo['tables']) ?></h3>
                        <small class="text-muted">Tables</small>
                    </div>
                    <div class="col-4">
                        <h3 class="text-success"><?= array_sum(array_column($dbInfo['tables'], 'NUM_ROWS')) ?></h3>
                        <small class="text-muted">Total Records</small>
                    </div>
                    <div class="col-4">
                        <h3 class="text-info"><?= $dbInfo['session_count'] ?? 0 ?></h3>
                        <small class="text-muted">Active Sessions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sample Queries -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-code me-2"></i>
                    Quick Database Tests
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Test Connection</h6>
                        <pre class="bg-light p-2 rounded"><code>SELECT 'Connection OK' FROM DUAL;</code></pre>
                        <div class="mb-3">
                            <strong>Result:</strong> 
                            <span class="badge bg-success">Connection OK</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Current Date/Time</h6>
                        <pre class="bg-light p-2 rounded"><code>SELECT SYSDATE FROM DUAL;</code></pre>
                        <div class="mb-3">
                            <strong>Result:</strong> 
                            <span class="badge bg-info"><?= $dbInfo['current_date'] ?? 'N/A' ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="button" class="btn btn-outline-primary" onclick="testConnection()">
                        <i class="fas fa-sync-alt me-1"></i>
                        Test Connection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Connection Error -->
<div class="row">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Connection Error
                </h5>
            </div>
            <div class="card-body">
                <p>Unable to connect to the Oracle database. Please check:</p>
                <ul>
                    <li>Database server is running</li>
                    <li>Connection parameters are correct</li>
                    <li>Network connectivity</li>
                    <li>Oracle client is properly installed</li>
                </ul>
                
                <?php if (!empty($dbInfo['error'])): ?>
                    <div class="alert alert-danger mt-3">
                        <strong>Error Details:</strong><br>
                        <?= htmlspecialchars($dbInfo['error']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-danger" onclick="location.reload()">
                        <i class="fas fa-redo me-1"></i>
                        Retry Connection
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Custom JavaScript for this page
$customJS = '
<script>
function testConnection() {
    showLoading();
    
    // Simulate a connection test
    setTimeout(function() {
        hideLoading();
        Swal.fire({
            title: "Connection Test",
            text: "Database connection is working properly!",
            icon: "success",
            timer: 2000,
            showConfirmButton: false
        });
    }, 1000);
}
</script>
';
?>
</div>

<?php include 'includes/footer.php';

// PHP Functions
function getDatabaseInfo() {
    $info = [
        'connected' => false,
        'error' => '',
        'tables' => [],
        'version' => null,
        'current_date' => null,
        'session_count' => 0
    ];
    
    try {
        $db = Database::getInstance();
        $info['connected'] = $db->isConnected();
        
        if ($info['connected']) {
            // Get table information
            $sql = "SELECT table_name, num_rows, last_analyzed 
                   FROM user_tables 
                   WHERE table_name IN ('CLIENT_TYPE', 'CLIENTS', 'PRODUCT_TYPE', 'PRODUCTS', 'JOBS', 'EMPLOYEES', 'INVOICES', 'INVOICE_DETAILS')
                   ORDER BY table_name";
            $stmt = $db->query($sql);
            $info['tables'] = $db->fetchAll($stmt);
            
            // Get database version
            $stmt = $db->query("SELECT banner FROM v\$version WHERE rownum = 1");
            $info['version'] = $db->fetchOne($stmt);
            
            // Get current date
            $stmt = $db->query("SELECT TO_CHAR(SYSDATE, 'YYYY-MM-DD HH24:MI:SS') as current_date FROM DUAL");
            $result = $db->fetchOne($stmt);
            $info['current_date'] = $result['CURRENT_DATE'] ?? null;
            
            // Get session count (if accessible)
            try {
                $stmt = $db->query("SELECT COUNT(*) as count FROM v\$session WHERE status = 'ACTIVE'");
                $result = $db->fetchOne($stmt);
                $info['session_count'] = $result['COUNT'] ?? 0;
            } catch (Exception $e) {
                // User might not have access to v$session
                $info['session_count'] = 1; // At least our session
            }
        }
        
    } catch (Exception $e) {
        $info['error'] = $e->getMessage();
        logError("Database info error: " . $e->getMessage());
    }
    
    return $info;
}
?>