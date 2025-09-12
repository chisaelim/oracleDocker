<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';

// Handle form submission for adding new client type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $type_name = trim($_POST['type_name']);
        $discount_rate = floatval($_POST['discount_rate']);
        $remarks = trim($_POST['remarks']);
        
        // Validate required fields
        if (empty($type_name)) {
            throw new Exception("Type name is required.");
        }
        
        // Insert new client type
        $sql = "INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) 
                VALUES (:type_name, :discount_rate, :remarks)";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':type_name', $type_name);
        oci_bind_by_name($stmt, ':discount_rate', $discount_rate);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Client type added successfully!";
            // Clear form data
            $_POST = array();
        } else {
            $error = oci_error($stmt);
            throw new Exception("Database error: " . $error['message']);
        }
        
        oci_free_statement($stmt);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        if (isset($stmt)) {
            oci_free_statement($stmt);
        }
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $client_type_id = intval($_POST['client_type_id']);
        
        $sql = "DELETE FROM Client_Type WHERE CLIENT_TYPE = :client_type_id";
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':client_type_id', $client_type_id);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Client type deleted successfully!";
        } else {
            $error = oci_error($stmt);
            throw new Exception("Database error: " . $error['message']);
        }
        
        oci_free_statement($stmt);
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        if (isset($stmt)) {
            oci_free_statement($stmt);
        }
    }
}

// Get all client types
try {
    $client_types = DatabaseOCI8::query("
        SELECT ct.CLIENT_TYPE, ct.TYPE_NAME, ct.DISCOUNT_RATE, ct.REMARKS,
               COUNT(c.CLIENT_NO) as CLIENT_COUNT
        FROM Client_Type ct
        LEFT JOIN Clients c ON ct.CLIENT_TYPE = c.CLIENT_TYPE
        GROUP BY ct.CLIENT_TYPE, ct.TYPE_NAME, ct.DISCOUNT_RATE, ct.REMARKS
        ORDER BY ct.TYPE_NAME
    ");
} catch (Exception $e) {
    $client_types = [];
    $error_message = "Error loading client types: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Client Types Management
                    </h4>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add New Client Type Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Client Type
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="client_types.php">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="type_name" class="form-label">Type Name *</label>
                                        <input type="text" class="form-control" id="type_name" name="type_name" 
                                               value="<?php echo htmlspecialchars($_POST['type_name'] ?? ''); ?>" 
                                               required maxlength="30">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="discount_rate" class="form-label">Discount Rate (%)</label>
                                        <input type="number" class="form-control" id="discount_rate" name="discount_rate" 
                                               value="<?php echo htmlspecialchars($_POST['discount_rate'] ?? '0'); ?>" 
                                               min="0" max="100" step="0.01">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="remarks" class="form-label">Remarks</label>
                                        <input type="text" class="form-control" id="remarks" name="remarks" 
                                               value="<?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?>" 
                                               maxlength="50">
                                    </div>
                                    <div class="col-md-2 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save me-1"></i>Add
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Client Types Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Type Name</th>
                                    <th>Discount Rate</th>
                                    <th>Remarks</th>
                                    <th>Clients Using</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($client_types)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                            <div class="text-muted">No client types available.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($client_types as $type): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($type['CLIENT_TYPE'] ?? ''); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($type['TYPE_NAME'] ?? ''); ?></strong>
                                            </td>
                                            <td>
                                                <?php if (!empty($type['DISCOUNT_RATE'])): ?>
                                                    <span class="badge bg-success">
                                                        <?php echo number_format($type['DISCOUNT_RATE'], 2); ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">0%</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($type['REMARKS'] ?? ''); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <?php echo intval($type['CLIENT_COUNT'] ?? 0); ?> clients
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            title="Edit Client Type">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (intval($type['CLIENT_COUNT']) == 0): ?>
                                                        <form method="POST" action="client_types.php" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('Are you sure you want to delete this client type?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="client_type_id" value="<?php echo $type['CLIENT_TYPE']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                    title="Delete Client Type">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                title="Cannot delete - has clients" disabled>
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once './includes/footer.php'; ?>