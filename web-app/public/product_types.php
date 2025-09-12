<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';

// Handle form submission for adding new product type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $producttype_name = trim($_POST['producttype_name']);
        $remarks = trim($_POST['remarks']);
        
        // Validate required fields
        if (empty($producttype_name)) {
            throw new Exception("Product type name is required.");
        }
        
        // Insert new product type
        $sql = "INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) 
                VALUES (:producttype_name, :remarks)";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':producttype_name', $producttype_name);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Product type added successfully!";
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
        $producttype_id = intval($_POST['producttype_id']);
        
        $sql = "DELETE FROM Product_Type WHERE PRODUCTTYPE_ID = :producttype_id";
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':producttype_id', $producttype_id);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Product type deleted successfully!";
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

// Get all product types
try {
    $product_types = DatabaseOCI8::query("
        SELECT pt.PRODUCTTYPE_ID, pt.PRODUCTTYPE_NAME, pt.REMARKS,
               COUNT(p.PRODUCT_NO) as PRODUCT_COUNT
        FROM Product_Type pt
        LEFT JOIN Products p ON pt.PRODUCTTYPE_ID = p.PRODUCTTYPE
        GROUP BY pt.PRODUCTTYPE_ID, pt.PRODUCTTYPE_NAME, pt.REMARKS
        ORDER BY pt.PRODUCTTYPE_NAME
    ");
} catch (Exception $e) {
    $product_types = [];
    $error_message = "Error loading product types: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>Product Types Management
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

                    <!-- Add New Product Type Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Add New Product Type
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="product_types.php">
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label for="producttype_name" class="form-label">Product Type Name *</label>
                                        <input type="text" class="form-control" id="producttype_name" name="producttype_name" 
                                               value="<?php echo htmlspecialchars($_POST['producttype_name'] ?? ''); ?>" 
                                               required maxlength="50">
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label for="remarks" class="form-label">Remarks</label>
                                        <input type="text" class="form-control" id="remarks" name="remarks" 
                                               value="<?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?>" 
                                               maxlength="30">
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

                    <!-- Product Types Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Product Type Name</th>
                                    <th>Remarks</th>
                                    <th>Products Using</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($product_types)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                                            <div class="text-muted">No product types available.</div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($product_types as $type): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($type['PRODUCTTYPE_ID'] ?? ''); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($type['PRODUCTTYPE_NAME'] ?? ''); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($type['REMARKS'] ?? ''); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <?php echo intval($type['PRODUCT_COUNT'] ?? 0); ?> products
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            title="Edit Product Type">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (intval($type['PRODUCT_COUNT']) == 0): ?>
                                                        <form method="POST" action="product_types.php" 
                                                              class="d-inline" 
                                                              onsubmit="return confirm('Are you sure you want to delete this product type?')">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="producttype_id" value="<?php echo $type['PRODUCTTYPE_ID']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                    title="Delete Product Type">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                                title="Cannot delete - has products" disabled>
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