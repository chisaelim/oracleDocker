<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';
$client_id = $_GET['id'] ?? null;

if (!$client_id) {
    header('Location: clients.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clientname = trim($_POST['clientname']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $phone = trim($_POST['phone']);
        $client_type = $_POST['client_type'];
        
        // Get discount rate from client type
        $discount_rate = 0;
        if (!empty($client_type)) {
            $type_info = DatabaseOCI8::queryOne("SELECT DISCOUNT_RATE FROM Client_Type WHERE CLIENT_TYPE = :client_type", [':client_type' => $client_type]);
            if ($type_info) {
                $discount_rate = $type_info['DISCOUNT_RATE'];
            }
        }
        
        // Validate required fields
        if (empty($clientname) || empty($phone)) {
            throw new Exception("Client name and phone are required fields.");
        }
        
        // Update client with discount from client type
        $sql = "UPDATE Clients SET 
                CLIENTNAME = :clientname, 
                ADDRESS = :address, 
                CITY = :city, 
                PHONE = :phone, 
                CLIENT_TYPE = :client_type,
                DISCOUNT = :discount
                WHERE CLIENT_NO = :client_id";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':clientname', $clientname);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':city', $city);
        oci_bind_by_name($stmt, ':phone', $phone);
        oci_bind_by_name($stmt, ':client_type', $client_type);
        oci_bind_by_name($stmt, ':discount', $discount_rate);
        oci_bind_by_name($stmt, ':client_id', $client_id);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Client updated successfully!";
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

// Get client data
try {
    $client = DatabaseOCI8::queryOne("
        SELECT c.*, ct.TYPE_NAME 
        FROM Clients c 
        LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE 
        WHERE c.CLIENT_NO = :client_id
    ", [':client_id' => $client_id]);
    
    if (!$client) {
        throw new Exception("Client not found.");
    }
} catch (Exception $e) {
    $error_message = "Error loading client: " . $e->getMessage();
    $client = null;
}

// Get client types for dropdown with discount rates
try {
    $client_types = DatabaseOCI8::query("SELECT CLIENT_TYPE, TYPE_NAME, DISCOUNT_RATE FROM Client_Type ORDER BY TYPE_NAME");
} catch (Exception $e) {
    $client_types = [];
    $error_message = "Error loading client types: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit Client
                        <?php if ($client): ?>
                            - <?php echo htmlspecialchars($client['CLIENTNAME']); ?>
                        <?php endif; ?>
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

                    <?php if ($client): ?>
                    <form method="POST" action="edit_client.php?id=<?php echo $client_id; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clientname" class="form-label">
                                    <i class="fas fa-user me-1"></i>Client Name *
                                </label>
                                <input type="text" class="form-control" id="clientname" name="clientname" 
                                       value="<?php echo htmlspecialchars($_POST['clientname'] ?? $client['CLIENTNAME']); ?>" 
                                       required maxlength="50">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Phone *
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? $client['PHONE']); ?>" 
                                       required maxlength="15">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Address
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      maxlength="150"><?php echo htmlspecialchars($_POST['address'] ?? $client['ADDRESS']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">
                                    <i class="fas fa-city me-1"></i>City
                                </label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? $client['CITY']); ?>" 
                                       maxlength="50">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="client_type" class="form-label">
                                    <i class="fas fa-tags me-1"></i>Client Type
                                </label>
                                <select class="form-select" id="client_type" name="client_type" onchange="updateDiscount()">
                                    <option value="">Select Client Type</option>
                                    <?php foreach ($client_types as $type): ?>
                                        <option value="<?php echo $type['CLIENT_TYPE']; ?>" 
                                                data-discount="<?php echo $type['DISCOUNT_RATE']; ?>"
                                                <?php echo (($_POST['client_type'] ?? $client['CLIENT_TYPE']) == $type['CLIENT_TYPE']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['TYPE_NAME']); ?>
                                            <?php if ($type['DISCOUNT_RATE'] > 0): ?>
                                                (<?php echo number_format($type['DISCOUNT_RATE'], 2); ?>% discount)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="discount_display" class="form-label">
                                    <i class="fas fa-percent me-1"></i>Discount Rate
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="discount_display" readonly 
                                           style="background-color: #e9ecef;" 
                                           value="<?php echo number_format($_POST['discount'] ?? $client['DISCOUNT'] ?? 0, 2); ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="form-text text-muted">Discount is automatically set based on client type</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info py-2" id="discount_info" style="display: none;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="discount_message"></span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="clients.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Clients
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Client
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <div class="text-muted">Client not found or could not be loaded.</div>
                            <a href="clients.php" class="btn btn-primary mt-3">
                                <i class="fas fa-arrow-left me-1"></i>Back to Clients
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateDiscount() {
    const clientTypeSelect = document.getElementById('client_type');
    const discountDisplay = document.getElementById('discount_display');
    const discountInfo = document.getElementById('discount_info');
    const discountMessage = document.getElementById('discount_message');
    
    const selectedOption = clientTypeSelect.options[clientTypeSelect.selectedIndex];
    
    if (selectedOption.value === '') {
        discountDisplay.value = '';
        discountInfo.style.display = 'none';
        return;
    }
    
    const discountRate = parseFloat(selectedOption.getAttribute('data-discount')) || 0;
    const typeName = selectedOption.text.split(' (')[0]; // Get type name without discount info
    
    discountDisplay.value = discountRate.toFixed(2);
    
    if (discountRate > 0) {
        discountMessage.textContent = `${typeName} clients receive ${discountRate.toFixed(2)}% discount on all orders.`;
        discountInfo.className = 'alert alert-success py-2';
        discountInfo.style.display = 'block';
    } else {
        discountMessage.textContent = `${typeName} clients receive standard pricing.`;
        discountInfo.className = 'alert alert-info py-2';
        discountInfo.style.display = 'block';
    }
}

// Update discount on page load if client type is already selected
document.addEventListener('DOMContentLoaded', function() {
    updateDiscount();
});
</script>

<?php require_once './includes/footer.php'; ?>