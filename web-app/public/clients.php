<?php
// clients.php - Clients Management with CRUD Operations
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
$editClient = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editClient = getClientById($_GET['id']);
}

// Get all clients for display
$clients = getAllClients();
$clientTypes = getAllClientTypes();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Clients Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#clientModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i>Add Client
                    </button>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($clients)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Client Found</h5>
                            <p class="text-muted">Start by adding your first client.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientModal" onclick="resetForm()">
                                <i class="fas fa-plus me-1"></i>
                                Add Client
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="clientsTable" class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Client ID</th>
                                        <th>Client Name</th>
                                        <th>Address</th>
                                        <th>City</th>
                                        <th>Phone</th>
                                        <th>Client Type</th>
                                        <th>Discount %</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['CLIENT_NO']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($client['CLIENTNAME']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($client['ADDRESS'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($client['CITY'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($client['PHONE']); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($client['TYPE_NAME'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo number_format($client['DISCOUNT'], 2); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editClient(<?php echo htmlspecialchars(json_encode($client)); ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#clientModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $client['CLIENT_NO']; ?>, '<?php echo htmlspecialchars($client['CLIENTNAME']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Client Modal -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientModalLabel">Add New Client</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="clientForm">
                <div class="modal-body">
                    <input type="hidden" id="clientId" name="id">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="clientName" class="form-label">Client Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="clientName" name="client_name" required maxlength="50">
                        <div class="form-text">Maximum 50 characters</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <input type="text" class="form-control" id="address" name="address" maxlength="150">
                                <div class="form-text">Maximum 150 characters</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" maxlength="50">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" required maxlength="15">
                                <div class="form-text">Maximum 15 characters</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="clientType" class="form-label">Client Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="clientType" name="client_type" required onchange="updateDiscountFromType()">
                                    <option value="">Select Client Type</option>
                                    <?php foreach ($clientTypes as $type): ?>
                                        <option value="<?php echo $type['CLIENT_TYPE']; ?>" 
                                                data-discount="<?php echo $type['DISCOUNT_RATE']; ?>">
                                            <?php echo htmlspecialchars($type['TYPE_NAME']); ?> 
                                            (<?php echo number_format($type['DISCOUNT_RATE'], 2); ?>%)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="discount" class="form-label">Discount Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="discount" name="discount" 
                               min="0" max="100" step="0.01" value="0.00" required>
                        <div class="form-text">
                            <span id="discountHelp">Will be auto-filled when you select a client type</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-1"></i>Save Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

// Reset form for new client
function resetForm() {
    document.getElementById('clientForm').reset();
    document.getElementById('clientId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('clientModalLabel').textContent = 'Add New Client';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Client';
    document.getElementById('discount').value = '0.00';
    document.getElementById('discountHelp').textContent = 'Will be auto-filled when you select a client type';
}

// Edit client function
function editClient(client) {
    document.getElementById('clientId').value = client.CLIENT_NO;
    document.getElementById('formAction').value = 'update';
    document.getElementById('clientName').value = client.CLIENTNAME;
    document.getElementById('address').value = client.ADDRESS || '';
    document.getElementById('city').value = client.CITY || '';
    document.getElementById('phone').value = client.PHONE;
    document.getElementById('clientType').value = client.CLIENT_TYPE;
    document.getElementById('discount').value = parseFloat(client.DISCOUNT).toFixed(2);
    
    document.getElementById('clientModalLabel').textContent = 'Edit Client';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Client';
    document.getElementById('discountHelp').textContent = 'You can customize the discount rate for this client';
}

// Update discount from client type selection
function updateDiscountFromType() {
    const select = document.getElementById('clientType');
    const discountInput = document.getElementById('discount');
    const discountHelp = document.getElementById('discountHelp');
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const typeDiscount = selectedOption.getAttribute('data-discount');
        
        // Only update if it's a new client (create mode)
        if (document.getElementById('formAction').value === 'create') {
            discountInput.value = parseFloat(typeDiscount).toFixed(2);
            discountHelp.textContent = `Discount rate from ${selectedOption.text}. You can modify if needed.`;
        }
    }
}

// Confirm delete
function confirmDelete(clientId, clientName) {
    Swal.fire({
        title: 'Confirm Deletion',
        text: `Are you sure you want to delete client "${clientName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `clients.php?action=delete&id=${clientId}`;
        }
    });
}
</script>

<?php
// PHP Functions

function getAllClients() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT c.CLIENT_NO, c.CLIENTNAME, c.ADDRESS, c.CITY, c.PHONE, 
                       c.CLIENT_TYPE, c.DISCOUNT, ct.TYPE_NAME
                FROM Clients c
                LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
                ORDER BY c.CLIENTNAME";
        
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        setFlashMessage('Error fetching clients: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function getAllClientTypes() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT CLIENT_TYPE, TYPE_NAME, DISCOUNT_RATE FROM Client_Type ORDER BY TYPE_NAME";
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        setFlashMessage('Error fetching client types: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function getClientById($id) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT * FROM Clients WHERE CLIENT_NO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        $id_param = $id;
        oci_bind_by_name($stmt, ':id', $id_param);
        oci_execute($stmt);
        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $result;
    } catch (Exception $e) {
        setFlashMessage('Error fetching client: ' . $e->getMessage(), 'danger');
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
        createClient($_POST);
    } elseif ($action === 'update') {
        updateClient($_POST);
    }
}

function createClient($data) {
    try {
        // Validate required fields
        if (empty($data['client_name']) || empty($data['phone']) || empty($data['client_type'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        // Get discount rate from client type if not provided
        $discount = !empty($data['discount']) ? $data['discount'] : 0;
        if ($discount == 0) {
            $typeSql = "SELECT DISCOUNT_RATE FROM Client_Type WHERE CLIENT_TYPE = :client_type";
            $typeStmt = oci_parse($db->getConnection(), $typeSql);
            $client_type_param = $data['client_type'];
            oci_bind_by_name($typeStmt, ':client_type', $client_type_param);
            oci_execute($typeStmt);
            $typeResult = oci_fetch_assoc($typeStmt);
            if ($typeResult) {
                $discount = $typeResult['DISCOUNT_RATE'];
            }
            oci_free_statement($typeStmt);
        }
        
        $sql = "INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE, DISCOUNT) 
                VALUES (:client_name, :address, :city, :phone, :client_type, :discount)";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        // Extract values to variables for binding by reference
        $client_name = $data['client_name'];
        $address = $data['address'] ?? null;
        $city = $data['city'] ?? null;
        $phone = $data['phone'];
        $client_type = $data['client_type'];
        $discount_value = $discount;
        
        oci_bind_by_name($stmt, ':client_name', $client_name);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':city', $city);
        oci_bind_by_name($stmt, ':phone', $phone);
        oci_bind_by_name($stmt, ':client_type', $client_type);
        oci_bind_by_name($stmt, ':discount', $discount_value);
        
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Client created successfully!', 'success');
        header('Location: clients.php');
        exit;
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        oci_rollback($db->getConnection());
        
        if (strpos($e->getMessage(), 'unique constraint') !== false) {
            if (strpos($e->getMessage(), 'CLIENTNAME') !== false) {
                setFlashMessage('A client with this name already exists.', 'danger');
            } elseif (strpos($e->getMessage(), 'PHONE') !== false) {
                setFlashMessage('A client with this phone number already exists.', 'danger');
            } else {
                setFlashMessage('Duplicate entry detected.', 'danger');
            }
        } else {
            setFlashMessage('Error creating client: ' . $e->getMessage(), 'danger');
        }
    }
}

function updateClient($data) {
    try {
        // Validate required fields
        if (empty($data['client_name']) || empty($data['phone']) || empty($data['client_type']) || empty($data['id'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        $sql = "UPDATE Clients SET CLIENTNAME = :client_name, ADDRESS = :address, CITY = :city, 
                PHONE = :phone, CLIENT_TYPE = :client_type, DISCOUNT = :discount 
                WHERE CLIENT_NO = :id";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        // Extract values to variables for binding by reference
        $client_name = $data['client_name'];
        $address = $data['address'] ?? null;
        $city = $data['city'] ?? null;
        $phone = $data['phone'];
        $client_type = $data['client_type'];
        $discount = $data['discount'];
        $id = $data['id'];
        
        oci_bind_by_name($stmt, ':client_name', $client_name);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':city', $city);
        oci_bind_by_name($stmt, ':phone', $phone);
        oci_bind_by_name($stmt, ':client_type', $client_type);
        oci_bind_by_name($stmt, ':discount', $discount);
        oci_bind_by_name($stmt, ':id', $id);
        
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Client updated successfully!', 'success');
        header('Location: clients.php');
        exit;
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        oci_rollback($db->getConnection());
        
        if (strpos($e->getMessage(), 'unique constraint') !== false) {
            if (strpos($e->getMessage(), 'CLIENTNAME') !== false) {
                setFlashMessage('A client with this name already exists.', 'danger');
            } elseif (strpos($e->getMessage(), 'PHONE') !== false) {
                setFlashMessage('A client with this phone number already exists.', 'danger');
            } else {
                setFlashMessage('Duplicate entry detected.', 'danger');
            }
        } else {
            setFlashMessage('Error updating client: ' . $e->getMessage(), 'danger');
        }
    }
}

function handleDelete($id) {
    try {
        $db = Database::getInstance();
        
        // Check if client is being used in any invoices (you can expand this)
        // For now, we'll allow deletion but you can add referential integrity checks
        
        $sql = "DELETE FROM Clients WHERE CLIENT_NO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        $id_param = $id;
        oci_bind_by_name($stmt, ':id', $id_param);
        
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Client deleted successfully!', 'success');
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        oci_rollback($db->getConnection());
        setFlashMessage('Error deleting client: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: clients.php');
    exit;
}

require_once 'includes/footer.php';
?>