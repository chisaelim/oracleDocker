<?php
$page_title = 'Database Information';
require_once 'includes/header.php';

// Check if OCI8 extension is available and use appropriate database config
if (extension_loaded('oci8')) {
    require_once 'config/database_oci8.php';
    $use_oci8 = true;
} else {
    $use_oci8 = false;
}

try {
    if ($use_oci8) {
        $connection_test = DatabaseOCI8::testConnection();
        $db_info = DatabaseOCI8::getDatabaseInfo();
        
        if ($connection_test['success']) {
            // Get table information
            $tables = DatabaseOCI8::query("
                SELECT TABLE_NAME, NUM_ROWS, LAST_ANALYZED 
                FROM USER_TABLES 
                ORDER BY TABLE_NAME
            ");
            
            // Get session information
            $session_info = DatabaseOCI8::queryOne("
                SELECT 
                    SYS_CONTEXT('USERENV', 'SESSION_USER') as SESSION_USER,
                    SYS_CONTEXT('USERENV', 'CURRENT_USER') as CURRENT_USER,
                    SYS_CONTEXT('USERENV', 'DB_NAME') as DB_NAME,
                    SYS_CONTEXT('USERENV', 'SERVER_HOST') as SERVER_HOST,
                    SYS_CONTEXT('USERENV', 'IP_ADDRESS') as IP_ADDRESS,
                    SYSDATE as CURRENT_TIME
                FROM DUAL
            ");
        } else {
            $tables = [];
            $session_info = null;
        }
    } else {
        $connection_test = ['success' => false, 'message' => 'OCI8 extension not available'];
        $db_info = ['error' => 'OCI8 extension not available'];
        $tables = [];
        $session_info = null;
    }
} catch (Exception $e) {
    $connection_test = ['success' => false, 'message' => $e->getMessage()];
    $db_info = ['error' => $e->getMessage()];
    $tables = [];
    $session_info = null;
}
?>

<div class="page-header">
    <div class="container">
        <h1>
            <i class="fas fa-database me-3"></i>
            Database Information
        </h1>
        <p class="lead mb-0">Oracle Database Connection and System Details</p>
    </div>
</div>

<!-- Connection Status -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plug me-2"></i>
                    Connection Status
                </h5>
            </div>
            <div class="card-body">
                <div class="connection-status <?php echo $connection_test['success'] ? 'connected' : 'disconnected'; ?> mb-3">
                    <i class="fas fa-<?php echo $connection_test['success'] ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $connection_test['success'] ? 'Connected Successfully' : 'Connection Failed'; ?>
                </div>
                
                <?php if ($connection_test['success']): ?>
                    <p class="mb-1"><strong>Status:</strong> <?php echo htmlspecialchars($connection_test['message']); ?></p>
                    <p class="mb-0"><strong>Timestamp:</strong> <?php echo htmlspecialchars($connection_test['timestamp']); ?></p>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo htmlspecialchars($connection_test['message']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($connection_test['success']): ?>
    <!-- Database Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Database Details
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($db_info['error'])): ?>
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> <?php echo htmlspecialchars($db_info['error']); ?>
                        </div>
                    <?php else: ?>
                        <dl class="row">
                            <dt class="col-sm-4">Version:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($db_info['VERSION'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-4">Database Name:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($db_info['DB_NAME'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-4">Current User:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($db_info['CURRENT_USER'] ?? 'N/A'); ?></dd>
                            
                            <dt class="col-sm-4">Session User:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($db_info['SESSION_USER'] ?? 'N/A'); ?></dd>
                        </dl>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-server me-2"></i>
                        Session Information
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($session_info): ?>
                        <dl class="row">
                            <dt class="col-sm-4">Session User:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($session_info['SESSION_USER']); ?></dd>
                            
                            <dt class="col-sm-4">Current User:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($session_info['CURRENT_USER']); ?></dd>
                            
                            <dt class="col-sm-4">Database:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($session_info['DB_NAME']); ?></dd>
                            
                            <dt class="col-sm-4">Server Host:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($session_info['SERVER_HOST']); ?></dd>
                            
                            <dt class="col-sm-4">IP Address:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($session_info['IP_ADDRESS']); ?></dd>
                            
                            <dt class="col-sm-4">Current Time:</dt>
                            <dd class="col-sm-8"><?php echo htmlspecialchars($session_info['CURRENT_TIME']); ?></dd>
                        </dl>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Unable to retrieve session information.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tables Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        Database Tables
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($tables)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Row Count</th>
                                        <th>Last Analyzed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tables as $table): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($table['TABLE_NAME']); ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($table['NUM_ROWS']): ?>
                                                    <span class="badge bg-primary">
                                                        <?php echo number_format($table['NUM_ROWS']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $table['LAST_ANALYZED'] ? Utils::formatDate($table['LAST_ANALYZED']) : '<span class="text-muted">Never</span>'; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="showTableStructure('<?php echo htmlspecialchars($table['TABLE_NAME']); ?>')"
                                                        data-bs-toggle="tooltip" 
                                                        title="View table structure">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No tables found or unable to retrieve table information.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
<?php else: ?>
    <!-- Connection Failed -->
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Connection Failed
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h6>Unable to connect to the Oracle database.</h6>
                        <p class="mb-0">Please check the following:</p>
                        <ul class="mt-2 mb-0">
                            <li>Database server is running</li>
                            <li>Connection parameters are correct</li>
                            <li>Network connectivity</li>
                            <li>Oracle Instant Client is properly installed</li>
                        </ul>
                    </div>
                    
                    <div class="text-center">
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>
                            Retry Connection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal for Table Structure -->
<div class="modal fade" id="tableStructureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Table Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tableStructureContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTableStructure(tableName) {
    const modal = new bootstrap.Modal(document.getElementById('tableStructureModal'));
    const content = document.getElementById('tableStructureContent');
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // In a real implementation, you would fetch the table structure via AJAX
    content.innerHTML = `
        <div class="alert alert-info">
            <strong>Table:</strong> ${tableName}<br>
            <em>Table structure details would be loaded here via AJAX call.</em>
        </div>
    `;
}
</script>

<?php require_once 'includes/footer.php'; ?>