<?php
// product_types.php - Product Types Management with CRUD Operations
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/utils.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleFormSubmission();
}

// Handle delete requests
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    handleDelete($_GET['id']);
}

// Handle edit requests
$editProductType = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editProductType = getProductTypeById($_GET['id']);
}

// Get all product types for display
$productTypes = getAllProductTypes();

function handleFormSubmission() {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        return;
    }
    
    $action = $_POST['action'];
    
    if ($action === 'create') {
        createProductType($_POST);
    } elseif ($action === 'update') {
        updateProductType($_POST);
    }
}

function createProductType($data) {
    try {
        // Validate required fields
        if (empty($data['producttype_name'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        if (strlen($data['producttype_name']) > 50) {
            setFlashMessage('Product Type Name must be 50 characters or less.', 'danger');
            return;
        }
        
        if (!empty($data['remarks']) && strlen($data['remarks']) > 30) {
            setFlashMessage('Remarks must be 30 characters or less.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        // Check if product type name already exists
        $checkSql = "SELECT COUNT(*) as count FROM Product_Type WHERE UPPER(PRODUCTTYPE_NAME) = UPPER(:producttype_name)";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        $producttype_name = $data['producttype_name'];
        oci_bind_by_name($checkStmt, ':producttype_name', $producttype_name);
        oci_execute($checkStmt);
        $result = oci_fetch_assoc($checkStmt);
        
        if ($result['COUNT'] > 0) {
            setFlashMessage('Product Type Name already exists.', 'danger');
            oci_free_statement($checkStmt);
            return;
        }
        
        oci_free_statement($checkStmt);
        
        // Insert new product type
        $sql = "INSERT INTO Product_Type (PRODUCTTYPE_NAME, REMARKS) VALUES (:producttype_name, :remarks)";
        $stmt = oci_parse($db->getConnection(), $sql);
        
        $remarks = $data['remarks'] ?? null;
        oci_bind_by_name($stmt, ':producttype_name', $producttype_name);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        
        if (oci_execute($stmt)) {
            setFlashMessage('Product Type created successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error creating product type: ' . $error['message'], 'danger');
        }
        
        oci_free_statement($stmt);
        
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: product_types.php');
    exit();
}

function updateProductType($data) {
    try {
        // Validate required fields
        if (empty($data['producttype_id']) || empty($data['producttype_name'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        if (strlen($data['producttype_name']) > 50) {
            setFlashMessage('Product Type Name must be 50 characters or less.', 'danger');
            return;
        }
        
        if (!empty($data['remarks']) && strlen($data['remarks']) > 30) {
            setFlashMessage('Remarks must be 30 characters or less.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        $producttype_id = (int)$data['producttype_id'];
        $producttype_name = $data['producttype_name'];
        
        // Check if product type name already exists (excluding current record)
        $checkSql = "SELECT COUNT(*) as count FROM Product_Type 
                     WHERE UPPER(PRODUCTTYPE_NAME) = UPPER(:producttype_name) 
                     AND PRODUCTTYPE_ID != :producttype_id";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        oci_bind_by_name($checkStmt, ':producttype_name', $producttype_name);
        oci_bind_by_name($checkStmt, ':producttype_id', $producttype_id);
        oci_execute($checkStmt);
        $result = oci_fetch_assoc($checkStmt);
        
        if ($result['COUNT'] > 0) {
            setFlashMessage('Product Type Name already exists.', 'danger');
            oci_free_statement($checkStmt);
            return;
        }
        
        oci_free_statement($checkStmt);
        
        // Update product type
        $sql = "UPDATE Product_Type 
                SET PRODUCTTYPE_NAME = :producttype_name, REMARKS = :remarks 
                WHERE PRODUCTTYPE_ID = :producttype_id";
        $stmt = oci_parse($db->getConnection(), $sql);
        
        $remarks = $data['remarks'] ?? null;
        oci_bind_by_name($stmt, ':producttype_name', $producttype_name);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        oci_bind_by_name($stmt, ':producttype_id', $producttype_id);
        
        if (oci_execute($stmt)) {
            setFlashMessage('Product Type updated successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error updating product type: ' . $error['message'], 'danger');
        }
        
        oci_free_statement($stmt);
        
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: product_types.php');
    exit();
}

function handleDelete($id) {
    try {
        $db = Database::getInstance();
        $producttype_id = (int)$id;
        
        // Check if product type is being used by any products
        $checkSql = "SELECT COUNT(*) as count FROM Products WHERE PRODUCTTYPE = :producttype_id";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        oci_bind_by_name($checkStmt, ':producttype_id', $producttype_id);
        oci_execute($checkStmt);
        $result = oci_fetch_assoc($checkStmt);
        
        if ($result['COUNT'] > 0) {
            setFlashMessage('Cannot delete Product Type. It is being used by ' . $result['COUNT'] . ' product(s).', 'danger');
            oci_free_statement($checkStmt);
            return;
        }
        
        oci_free_statement($checkStmt);
        
        // Delete product type
        $sql = "DELETE FROM Product_Type WHERE PRODUCTTYPE_ID = :producttype_id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':producttype_id', $producttype_id);
        
        if (oci_execute($stmt)) {
            setFlashMessage('Product Type deleted successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error deleting product type: ' . $error['message'], 'danger');
        }
        
        oci_free_statement($stmt);
        
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: product_types.php');
    exit();
}

function getAllProductTypes() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT PRODUCTTYPE_ID, PRODUCTTYPE_NAME, REMARKS 
                FROM Product_Type 
                ORDER BY PRODUCTTYPE_ID";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_execute($stmt);
        
        $productTypes = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $productTypes[] = $row;
        }
        
        oci_free_statement($stmt);
        return $productTypes;
        
    } catch (Exception $e) {
        setFlashMessage('Error fetching product types: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function getProductTypeById($id) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT PRODUCTTYPE_ID, PRODUCTTYPE_NAME, REMARKS 
                FROM Product_Type 
                WHERE PRODUCTTYPE_ID = :producttype_id";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        $producttype_id = (int)$id;
        oci_bind_by_name($stmt, ':producttype_id', $producttype_id);
        oci_execute($stmt);
        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $result;
        
    } catch (Exception $e) {
        setFlashMessage('Error fetching product type: ' . $e->getMessage(), 'danger');
        return null;
    }
}

// Get all product types for display
$productTypes = getAllProductTypes();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Product Types Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#productTypeModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i>Add Product Type
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($productTypes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Product Types Found</h5>
                            <p class="text-muted">Start by adding your first product type.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productTypeModal" onclick="resetForm()">
                                <i class="fas fa-plus me-1"></i>
                                Add Product Type
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product Type Name</th>
                                        <th>Remarks</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productTypes as $productType): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($productType['PRODUCTTYPE_ID']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($productType['PRODUCTTYPE_NAME']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($productType['REMARKS'] ?? '-') ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editProductType(<?= $productType['PRODUCTTYPE_ID'] ?>, '<?= htmlspecialchars($productType['PRODUCTTYPE_NAME'], ENT_QUOTES) ?>', '<?= htmlspecialchars($productType['REMARKS'] ?? '', ENT_QUOTES) ?>')"
                                                            data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="confirmDelete(<?= $productType['PRODUCTTYPE_ID'] ?>, '<?= htmlspecialchars($productType['PRODUCTTYPE_NAME'], ENT_QUOTES) ?>')"
                                                            data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Type Modal -->
<div class="modal fade" id="productTypeModal" tabindex="-1" aria-labelledby="productTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productTypeModalLabel">Add Product Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productTypeForm" method="POST" action="product_types.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" id="formAction" name="action" value="create">
                    <input type="hidden" id="productTypeId" name="producttype_id" value="">
                    
                    <div class="mb-3">
                        <label for="productTypeName" class="form-label">Product Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="productTypeName" name="producttype_name" 
                               maxlength="50" required>
                        <div class="form-text">Maximum 50 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" 
                                  maxlength="30" placeholder="Optional remarks"></textarea>
                        <div class="form-text">Maximum 30 characters</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Product Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Reset form when modal is opened for new product type
function resetForm() {
    document.getElementById('productTypeForm').reset();
    document.getElementById('productTypeId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('productTypeModalLabel').textContent = 'Add Product Type';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Product Type';
}

// Edit product type function
function editProductType(id, name, remarks) {
    document.getElementById('productTypeId').value = id;
    document.getElementById('formAction').value = 'update';
    document.getElementById('productTypeName').value = name;
    document.getElementById('remarks').value = remarks || '';
    
    document.getElementById('productTypeModalLabel').textContent = 'Edit Product Type';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Product Type';
    
    new bootstrap.Modal(document.getElementById('productTypeModal')).show();
}

// Confirm delete
function confirmDelete(typeId, typeName) {
    Swal.fire({
        title: 'Confirm Deletion',
        text: `Are you sure you want to delete product type "${typeName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `product_types.php?action=delete&id=${typeId}`;
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>