<?php
// client_types.php - Client Types Management with CRUD Operations
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
$editClientType = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editClientType = getClientTypeById($_GET['id']);
}

// Get all client types for display
$clientTypes = getAllClientTypes();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Client Types Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#clientTypeModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i>Add New Client Type
                    </button>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($clientTypes)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No client types found. Start by adding your first client type!
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type Name</th>
                                    <th>Discount Rate</th>
                                    <th>Remarks</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientTypes as $type): ?>
                                <tr>
                                    <td><?= htmlspecialchars($type['CLIENT_TYPE']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($type['TYPE_NAME']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= number_format($type['DISCOUNT_RATE'], 2) ?>%
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($type['REMARKS'] ?? '-') ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editClientType(<?= $type['CLIENT_TYPE'] ?>, '<?= htmlspecialchars($type['TYPE_NAME']) ?>', <?= $type['DISCOUNT_RATE'] ?>, '<?= htmlspecialchars($type['REMARKS']) ?>')"
                                                    data-bs-toggle="tooltip" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?= $type['CLIENT_TYPE'] ?>, '<?= htmlspecialchars($type['TYPE_NAME']) ?>')"
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

<!-- Client Type Modal -->
<div class="modal fade" id="clientTypeModal" tabindex="-1" aria-labelledby="clientTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientTypeModalLabel">Add New Client Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="clientTypeForm">
                <div class="modal-body">
                    <input type="hidden" id="clientTypeId" name="id">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="typeName" class="form-label">Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="typeName" name="type_name" required maxlength="30">
                        <div class="form-text">Maximum 30 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discountRate" class="form-label">Discount Rate (%)</label>
                        <input type="number" class="form-control" id="discountRate" name="discount_rate" 
                               min="0" max="100" step="0.01" value="0">
                        <div class="form-text">Enter discount rate as a percentage (0-100)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="50"></textarea>
                        <div class="form-text">Optional remarks about this client type (max 50 characters)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-1"></i>Save Client Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Reset form when modal is opened for new client type
function resetForm() {
    document.getElementById('clientTypeForm').reset();
    document.getElementById('clientTypeId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('clientTypeModalLabel').textContent = 'Add New Client Type';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Client Type';
}

// Edit client type function
function editClientType(id, name, discount, remarks) {
    document.getElementById('clientTypeId').value = id;
    document.getElementById('formAction').value = 'update';
    document.getElementById('typeName').value = name;
    document.getElementById('discountRate').value = parseFloat(discount).toFixed(2);
    document.getElementById('remarks').value = remarks || '';
    
    document.getElementById('clientTypeModalLabel').textContent = 'Edit Client Type';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Client Type';
    
    new bootstrap.Modal(document.getElementById('clientTypeModal')).show();
}

// Confirm delete
function confirmDelete(typeId, typeName) {
    Swal.fire({
        title: 'Confirm Deletion',
        text: `Are you sure you want to delete client type "${typeName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `client_types.php?action=delete&id=${typeId}`;
        }
    });
}
</script>

<?php require_once 'includes/footer.php';

// PHP Functions
function getAllClientTypes() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT CLIENT_TYPE, TYPE_NAME, DISCOUNT_RATE, REMARKS FROM Client_Type ORDER BY TYPE_NAME";
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        setFlashMessage("Error loading client types: " . $e->getMessage(), 'danger');
        return [];
    }
}

function getClientTypeById($id) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT CLIENT_TYPE, TYPE_NAME, DISCOUNT_RATE, REMARKS FROM Client_Type WHERE CLIENT_TYPE = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        $id_param = $id;
        oci_bind_by_name($stmt, ':id', $id_param);
        oci_execute($stmt);
        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $result;
    } catch (Exception $e) {
        return null;
    }
}

function handleFormSubmission() {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        return;
    }
    
    $action = $_POST['action'];
    
    if ($action === 'create') {
        createClientType($_POST);
    } elseif ($action === 'update') {
        updateClientType($_POST);
    }
}

function createClientType($data) {
    try {
        // Validate required fields
        if (empty($data['type_name'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        // Validate field lengths
        if (strlen($data['type_name']) > 30) {
            setFlashMessage('Type name must be 30 characters or less.', 'danger');
            return;
        }
        
        if (!empty($data['discount_rate']) && ($data['discount_rate'] < 0 || $data['discount_rate'] > 100)) {
            setFlashMessage('Discount rate must be between 0 and 100.', 'danger');
            return;
        }
        
        if (!empty($data['remarks']) && strlen($data['remarks']) > 50) {
            setFlashMessage('Remarks must be 50 characters or less.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        $sql = "INSERT INTO Client_Type (TYPE_NAME, DISCOUNT_RATE, REMARKS) 
                VALUES (:type_name, :discount_rate, :remarks)";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        // Extract values to variables for binding by reference
        $type_name = $data['type_name'];
        $discount_rate = $data['discount_rate'] ?: 0;
        $remarks = $data['remarks'] ?? null;
        
        oci_bind_by_name($stmt, ':type_name', $type_name);
        oci_bind_by_name($stmt, ':discount_rate', $discount_rate);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            throw new Exception('Database Error: ' . $error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Client type created successfully!', 'success');
        header('Location: client_types.php');
        exit;
        
    } catch (Exception $e) {
        setFlashMessage('Error creating client type: ' . $e->getMessage(), 'danger');
    }
}

function updateClientType($data) {
    try {
        // Validate required fields
        if (empty($data['type_name']) || empty($data['id'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        // Validate field lengths
        if (strlen($data['type_name']) > 30) {
            setFlashMessage('Type name must be 30 characters or less.', 'danger');
            return;
        }
        
        if (!empty($data['discount_rate']) && ($data['discount_rate'] < 0 || $data['discount_rate'] > 100)) {
            setFlashMessage('Discount rate must be between 0 and 100.', 'danger');
            return;
        }
        
        if (!empty($data['remarks']) && strlen($data['remarks']) > 50) {
            setFlashMessage('Remarks must be 50 characters or less.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        $sql = "UPDATE Client_Type SET TYPE_NAME = :type_name, DISCOUNT_RATE = :discount_rate, REMARKS = :remarks 
                WHERE CLIENT_TYPE = :id";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        // Extract values to variables for binding by reference
        $type_name = $data['type_name'];
        $discount_rate = $data['discount_rate'] ?: 0;
        $remarks = $data['remarks'] ?? null;
        $id = $data['id'];
        
        oci_bind_by_name($stmt, ':type_name', $type_name);
        oci_bind_by_name($stmt, ':discount_rate', $discount_rate);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        oci_bind_by_name($stmt, ':id', $id);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            throw new Exception('Database Error: ' . $error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Client type updated successfully!', 'success');
        header('Location: client_types.php');
        exit;
        
    } catch (Exception $e) {
        setFlashMessage('Error updating client type: ' . $e->getMessage(), 'danger');
    }
}

function handleDelete($id) {
    try {
        $db = Database::getInstance();
        
        // Check if client type is being used by any clients
        $checkSql = "SELECT COUNT(*) as count FROM Clients WHERE CLIENT_TYPE = :id";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        $id_param = $id;
        oci_bind_by_name($checkStmt, ':id', $id_param);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        
        if ($row && $row['COUNT'] > 0) {
            setFlashMessage('Cannot delete client type as it is being used by existing clients.', 'danger');
            oci_free_statement($checkStmt);
            header('Location: client_types.php');
            exit;
        }
        
        oci_free_statement($checkStmt);
        
        // Delete the client type
        $deleteSql = "DELETE FROM Client_Type WHERE CLIENT_TYPE = :id";
        $deleteStmt = oci_parse($db->getConnection(), $deleteSql);
        $delete_id = $id;
        oci_bind_by_name($deleteStmt, ':id', $delete_id);
        
        if (!oci_execute($deleteStmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($deleteStmt);
            throw new Exception('Database Error: ' . $error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($deleteStmt);
        
        setFlashMessage('Client type deleted successfully!', 'success');
        
    } catch (Exception $e) {
        setFlashMessage('Error deleting client type: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: client_types.php');
    exit;
}
?>