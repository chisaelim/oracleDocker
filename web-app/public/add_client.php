<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clientname = trim($_POST['clientname']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $phone = trim($_POST['phone']);
        $client_type = $_POST['client_type'];
        
        // Validate required fields
        if (empty($clientname) || empty($phone)) {
            throw new Exception("Client name and phone are required fields.");
        }
        
        // Insert new client
        $sql = "INSERT INTO Clients (CLIENTNAME, ADDRESS, CITY, PHONE, CLIENT_TYPE) 
                VALUES (:clientname, :address, :city, :phone, :client_type)";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':clientname', $clientname);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':city', $city);
        oci_bind_by_name($stmt, ':phone', $phone);
        oci_bind_by_name($stmt, ':client_type', $client_type);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Client added successfully!";
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

// Get client types for dropdown
try {
    $client_types = DatabaseOCI8::query("SELECT CLIENT_TYPE, TYPE_NAME FROM Client_Type ORDER BY TYPE_NAME");
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
                        <i class="fas fa-user-plus me-2"></i>Add New Client
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

                    <form method="POST" action="add_client.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clientname" class="form-label">
                                    <i class="fas fa-user me-1"></i>Client Name *
                                </label>
                                <input type="text" class="form-control" id="clientname" name="clientname" 
                                       value="<?php echo htmlspecialchars($_POST['clientname'] ?? ''); ?>" 
                                       required maxlength="50">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Phone *
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                       required maxlength="15">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Address
                            </label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      maxlength="150"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">
                                    <i class="fas fa-city me-1"></i>City
                                </label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" 
                                       maxlength="50">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="client_type" class="form-label">
                                    <i class="fas fa-tags me-1"></i>Client Type
                                </label>
                                <select class="form-select" id="client_type" name="client_type">
                                    <option value="">Select Client Type</option>
                                    <?php foreach ($client_types as $type): ?>
                                        <option value="<?php echo $type['CLIENT_TYPE']; ?>" 
                                                <?php echo (($_POST['client_type'] ?? '') == $type['CLIENT_TYPE']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['TYPE_NAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="clients.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Clients
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Add Client
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once './includes/footer.php'; ?>