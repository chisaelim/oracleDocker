<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $employeename = trim($_POST['employeename']);
        $gender = $_POST['gender'];
        $birthdate = $_POST['birthdate'];
        $job_id = $_POST['job_id'];
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $salary = floatval($_POST['salary']);
        $remarks = trim($_POST['remarks']);
        
        // Validate required fields
        if (empty($employeename)) {
            throw new Exception("Employee name is required.");
        }
        
        // Convert birthdate format if provided
        $birthdate_oracle = null;
        if (!empty($birthdate)) {
            $birthdate_oracle = $birthdate;
        }
        
        // Insert new employee
        $sql = "INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, ADDRESS, PHONE, SALARY, REMARKS) 
                VALUES (:employeename, :gender, " . 
                (!empty($birthdate_oracle) ? "TO_DATE(:birthdate, 'YYYY-MM-DD')" : "NULL") . 
                ", :job_id, :address, :phone, :salary, :remarks)";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':employeename', $employeename);
        oci_bind_by_name($stmt, ':gender', $gender);
        if (!empty($birthdate_oracle)) {
            oci_bind_by_name($stmt, ':birthdate', $birthdate_oracle);
        }
        oci_bind_by_name($stmt, ':job_id', $job_id);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':phone', $phone);
        oci_bind_by_name($stmt, ':salary', $salary);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Employee added successfully!";
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

// Get job titles for dropdown
try {
    $jobs = DatabaseOCI8::query("SELECT JOB_ID, JOB_TITLE FROM JOBS ORDER BY JOB_TITLE");
} catch (Exception $e) {
    $jobs = [];
    $error_message = "Error loading job titles: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Add New Employee
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

                    <form method="POST" action="add_employee.php">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="employeename" class="form-label">
                                    <i class="fas fa-user me-1"></i>Employee Name *
                                </label>
                                <input type="text" class="form-control" id="employeename" name="employeename" 
                                       value="<?php echo htmlspecialchars($_POST['employeename'] ?? ''); ?>" 
                                       required maxlength="50">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="gender" class="form-label">
                                    <i class="fas fa-venus-mars me-1"></i>Gender
                                </label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo (($_POST['gender'] ?? '') == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (($_POST['gender'] ?? '') == 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="birthdate" class="form-label">
                                    <i class="fas fa-birthday-cake me-1"></i>Birth Date
                                </label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate" 
                                       value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="job_id" class="form-label">
                                    <i class="fas fa-briefcase me-1"></i>Job Title
                                </label>
                                <select class="form-select" id="job_id" name="job_id">
                                    <option value="">Select Job Title</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?php echo $job['JOB_ID']; ?>" 
                                                <?php echo (($_POST['job_id'] ?? '') == $job['JOB_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['JOB_TITLE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Phone
                                </label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                       maxlength="15">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="salary" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Salary
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="salary" name="salary" 
                                           value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>" 
                                           min="0" step="0.01">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label">
                                <i class="fas fa-comment me-1"></i>Remarks
                            </label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                      maxlength="50" placeholder="Optional remarks or notes"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="employees.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Employees
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Add Employee
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once './includes/footer.php'; ?>