<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';
require_once './includes/utils.php';

$success_message = '';
$error_message = '';
$employee_id = $_GET['id'] ?? null;

if (!$employee_id) {
    header('Location: employees.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $firstname = trim($_POST['firstname']);
        $lastname = trim($_POST['lastname']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $zipcode = trim($_POST['zipcode']);
        $email = trim($_POST['email']);
        $job_id = $_POST['job_id'] ?: null;
        $birthdate = $_POST['birthdate'] ?: null;
        $salary = floatval($_POST['salary'] ?: 0);
        
        // Validate required fields
        if (empty($firstname) || empty($lastname)) {
            throw new Exception("First Name and Last Name are required fields.");
        }
        
        // Validate email format if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Update employee
        $sql = "UPDATE Employees SET 
                FIRSTNAME = :firstname, 
                LASTNAME = :lastname, 
                PHONE = :phone, 
                ADDRESS = :address, 
                CITY = :city, 
                STATE = :state, 
                ZIPCODE = :zipcode, 
                EMAIL = :email, 
                JOB_ID = :job_id, 
                BIRTHDATE = " . ($birthdate ? "TO_DATE(:birthdate, 'YYYY-MM-DD')" : "NULL") . ", 
                SALARY = :salary
                WHERE EMPLOYEE_ID = :employee_id";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':firstname', $firstname);
        oci_bind_by_name($stmt, ':lastname', $lastname);
        oci_bind_by_name($stmt, ':phone', $phone);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':city', $city);
        oci_bind_by_name($stmt, ':state', $state);
        oci_bind_by_name($stmt, ':zipcode', $zipcode);
        oci_bind_by_name($stmt, ':email', $email);
        oci_bind_by_name($stmt, ':job_id', $job_id);
        if ($birthdate) {
            oci_bind_by_name($stmt, ':birthdate', $birthdate);
        }
        oci_bind_by_name($stmt, ':salary', $salary);
        oci_bind_by_name($stmt, ':employee_id', $employee_id);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Employee updated successfully!";
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

// Get employee data
try {
    $employee = DatabaseOCI8::queryOne("
        SELECT e.*, j.JOB_TITLE, TO_CHAR(e.BIRTHDATE, 'YYYY-MM-DD') as BIRTHDATE_FORMATTED
        FROM Employees e 
        LEFT JOIN Jobs j ON e.JOB_ID = j.JOB_ID 
        WHERE e.EMPLOYEE_ID = :employee_id
    ", [':employee_id' => $employee_id]);
    
    if (!$employee) {
        throw new Exception("Employee not found.");
    }
} catch (Exception $e) {
    $error_message = "Error loading employee: " . $e->getMessage();
    $employee = null;
}

// Get jobs for dropdown
try {
    $jobs = DatabaseOCI8::query("SELECT JOB_ID, JOB_TITLE FROM Jobs ORDER BY JOB_TITLE");
} catch (Exception $e) {
    $jobs = [];
    $error_message = "Error loading jobs: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit Employee
                        <?php if ($employee): ?>
                            - <?php echo htmlspecialchars($employee['FIRSTNAME'] . ' ' . $employee['LASTNAME']); ?>
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

                    <?php if ($employee): ?>
                    <form method="POST" action="edit_employee.php?id=<?php echo urlencode($employee_id); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="employee_id" class="form-label">
                                    <i class="fas fa-id-badge me-1"></i>Employee ID
                                </label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                       value="<?php echo htmlspecialchars($employee['EMPLOYEE_ID']); ?>" 
                                       readonly style="background-color: #e9ecef;">
                                <small class="form-text text-muted">Employee ID cannot be changed</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="job_id" class="form-label">
                                    <i class="fas fa-briefcase me-1"></i>Job Title
                                </label>
                                <select class="form-select" id="job_id" name="job_id">
                                    <option value="">Select Job Title</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['JOB_ID']; ?>" 
                                                <?php echo (($_POST['job_id'] ?? $employee['JOB_ID']) == $job['JOB_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['JOB_TITLE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstname" class="form-label">
                                    <i class="fas fa-user me-1"></i>First Name *
                                </label>
                                <input type="text" class="form-control" id="firstname" name="firstname" 
                                       value="<?php echo htmlspecialchars($_POST['firstname'] ?? $employee['FIRSTNAME']); ?>" 
                                       required maxlength="20">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="lastname" class="form-label">
                                    <i class="fas fa-user me-1"></i>Last Name *
                                </label>
                                <input type="text" class="form-control" id="lastname" name="lastname" 
                                       value="<?php echo htmlspecialchars($_POST['lastname'] ?? $employee['LASTNAME']); ?>" 
                                       required maxlength="20">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Phone
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? $employee['PHONE']); ?>" 
                                       maxlength="12" placeholder="e.g., 555-123-4567">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? $employee['EMAIL']); ?>" 
                                       maxlength="40" placeholder="employee@example.com">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-home me-1"></i>Address
                                </label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($_POST['address'] ?? $employee['ADDRESS']); ?>" 
                                       maxlength="40" placeholder="Street address">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="city" class="form-label">
                                    <i class="fas fa-city me-1"></i>City
                                </label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? $employee['CITY']); ?>" 
                                       maxlength="20">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="state" class="form-label">
                                    <i class="fas fa-map me-1"></i>State
                                </label>
                                <input type="text" class="form-control" id="state" name="state" 
                                       value="<?php echo htmlspecialchars($_POST['state'] ?? $employee['STATE']); ?>" 
                                       maxlength="2" placeholder="e.g., CA">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="zipcode" class="form-label">
                                    <i class="fas fa-mail-bulk me-1"></i>ZIP Code
                                </label>
                                <input type="text" class="form-control" id="zipcode" name="zipcode" 
                                       value="<?php echo htmlspecialchars($_POST['zipcode'] ?? $employee['ZIPCODE']); ?>" 
                                       maxlength="5" placeholder="e.g., 12345">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="birthdate" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Birth Date
                                </label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                       value="<?php echo htmlspecialchars($_POST['birthdate'] ?? $employee['BIRTHDATE_FORMATTED']); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="salary" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Salary
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="salary" name="salary" 
                                           value="<?php echo htmlspecialchars($_POST['salary'] ?? $employee['SALARY']); ?>" 
                                           min="0" step="0.01" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="employees.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Employees
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Update Employee
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                            <div class="text-muted">Employee not found or could not be loaded.</div>
                            <a href="employees.php" class="btn btn-success mt-3">
                                <i class="fas fa-arrow-left me-1"></i>Back to Employees
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once './includes/footer.php'; ?>