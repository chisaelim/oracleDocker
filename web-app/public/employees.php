<?php
// employees.php - Employees Management with CRUD Operations and Photo Upload
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/utils.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleFormSubmission();
}

// Handle delete requests
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['employee_id'])) {
    handleDelete($_GET['employee_id']);
}

// Handle edit requests
$editEmployee = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editEmployee = getEmployeeById($_GET['id']);
}

// Get all employees and jobs for display
$employees = getAllEmployees();
$jobs = getAllJobs();

function handleFormSubmission()
{
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        return;
    }

    $action = $_POST['action'];

    if ($action === 'create') {
        createEmployee($_POST, $_FILES);
    } elseif ($action === 'update') {
        updateEmployee($_POST, $_FILES);
    }
}

function handleDelete($employeeId)
{
    try {
        $db = Database::getInstance();

        // Get the photo filename before deleting
        $photoSql = "SELECT PHOTO FROM Employees WHERE EMPLOYEEID = :employee_id";
        $photoStmt = oci_parse($db->getConnection(), $photoSql);
        oci_bind_by_name($photoStmt, ':employee_id', $employeeId);
        oci_execute($photoStmt);
        $photoResult = oci_fetch_assoc($photoStmt);
        oci_free_statement($photoStmt);

        // Delete the employee
        $sql = "DELETE FROM Employees WHERE EMPLOYEEID = :employee_id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':employee_id', $employeeId);

        if (oci_execute($stmt)) {
            // Delete the photo file if it exists
            if ($photoResult && !empty($photoResult['PHOTO'])) {
                $photoPath = 'uploads/employees/' . $photoResult['PHOTO'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }
            setFlashMessage('Employee deleted successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error deleting employee: ' . $error['message'], 'danger');
        }

        oci_free_statement($stmt);

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }

    header('Location: employees.php');
    exit();
}

function getEmployeeById($employeeId)
{
    try {
        $db = Database::getInstance();
        $sql = "SELECT e.EMPLOYEEID, e.EMPLOYEENAME, e.GENDER, e.BIRTHDATE, 
                       e.JOB_ID, e.ADDRESS, e.PHONE, e.SALARY, e.REMARKS, e.PHOTO,
                       j.JOB_TITLE, j.MIN_SALARY, j.MAX_SALARY
                FROM Employees e
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
                WHERE e.EMPLOYEEID = :employee_id";

        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':employee_id', $employeeId);
        oci_execute($stmt);
        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $result;

    } catch (Exception $e) {
        setFlashMessage('Error fetching employee: ' . $e->getMessage(), 'danger');
        return null;
    }
}

function getAllEmployees()
{
    try {
        $db = Database::getInstance();
        $sql = "SELECT e.EMPLOYEEID, e.EMPLOYEENAME, e.GENDER, e.BIRTHDATE, 
                       e.JOB_ID, e.ADDRESS, e.PHONE, e.SALARY, e.REMARKS, e.PHOTO,
                       j.JOB_TITLE, j.MIN_SALARY, j.MAX_SALARY
                FROM Employees e
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID
                ORDER BY e.EMPLOYEEID";

        $stmt = oci_parse($db->getConnection(), $sql);
        oci_execute($stmt);

        $employees = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $employees[] = $row;
        }

        oci_free_statement($stmt);
        return $employees;

    } catch (Exception $e) {
        setFlashMessage('Error fetching employees: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function getAllJobs()
{
    try {
        $db = Database::getInstance();
        $sql = "SELECT JOB_ID, JOB_TITLE, MIN_SALARY, MAX_SALARY FROM JOBS ORDER BY JOB_TITLE";

        $stmt = oci_parse($db->getConnection(), $sql);
        oci_execute($stmt);

        $jobs = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $jobs[] = $row;
        }

        oci_free_statement($stmt);
        return $jobs;

    } catch (Exception $e) {
        setFlashMessage('Error fetching jobs: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function createEmployee($data, $files)
{
    try {
        // Validate required fields
        if (empty($data['employee_name']) || empty($data['job_id']) || empty($data['salary'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }

        // Extract and validate data
        $employeeName = trim($data['employee_name']);
        $gender = trim($data['gender'] ?? '');
        $birthdate = trim($data['birthdate'] ?? '');
        $jobId = (int) $data['job_id'];
        $address = trim($data['address'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $salary = (int) ($data['salary'] ?? 0);
        $remarks = trim($data['remarks'] ?? '');

        // Validation
        if (strlen($employeeName) > 50) {
            setFlashMessage('Employee Name must be 50 characters or less.', 'danger');
            return;
        }

        if (!empty($phone) && strlen($phone) > 15) {
            setFlashMessage('Phone number must be 15 characters or less.', 'danger');
            return;
        }

        if ($salary <= 0) {
            setFlashMessage('Salary must be greater than 0.', 'danger');
            return;
        }

        $db = Database::getInstance();

        // Validate salary range against job
        $jobSql = "SELECT MIN_SALARY, MAX_SALARY, JOB_TITLE FROM JOBS WHERE JOB_ID = :job_id";
        $jobStmt = oci_parse($db->getConnection(), $jobSql);
        oci_bind_by_name($jobStmt, ':job_id', $jobId);
        oci_execute($jobStmt);
        $jobResult = oci_fetch_assoc($jobStmt);
        oci_free_statement($jobStmt);

        if (!$jobResult) {
            setFlashMessage('Invalid job selected.', 'danger');
            return;
        }

        if ($salary < $jobResult['MIN_SALARY'] || $salary > $jobResult['MAX_SALARY']) {
            setFlashMessage("Salary must be between $" . number_format($jobResult['MIN_SALARY']) . 
                          " and $" . number_format($jobResult['MAX_SALARY']) . 
                          " for " . $jobResult['JOB_TITLE'] . ".", 'danger');
            return;
        }

        // Check if phone number already exists (if provided)
        if (!empty($phone)) {
            $checkPhoneSql = "SELECT COUNT(*) as count FROM Employees WHERE PHONE = :phone";
            $checkPhoneStmt = oci_parse($db->getConnection(), $checkPhoneSql);
            oci_bind_by_name($checkPhoneStmt, ':phone', $phone);
            oci_execute($checkPhoneStmt);
            $phoneResult = oci_fetch_assoc($checkPhoneStmt);
            oci_free_statement($checkPhoneStmt);

            if ($phoneResult && $phoneResult['COUNT'] > 0) {
                setFlashMessage('Phone number already exists.', 'danger');
                return;
            }
        }

        // Handle photo upload
        $photoFilename = null;
        if (isset($files['employee_photo']) && $files['employee_photo']['error'] === UPLOAD_ERR_OK) {
            $photoFilename = processPhotoUpload($files['employee_photo'], 'emp_' . time());
        }

        // Convert empty birthdate to null
        $birthdateForDB = empty($birthdate) ? null : $birthdate;
        $phoneForDB = empty($phone) ? null : $phone;

        // Insert new employee
        $sql = "INSERT INTO Employees (EMPLOYEENAME, GENDER, BIRTHDATE, JOB_ID, 
                                      ADDRESS, PHONE, SALARY, REMARKS, PHOTO) 
                VALUES (:employee_name, :gender, TO_DATE(:birthdate, 'YYYY-MM-DD'), :job_id, 
                        :address, :phone, :salary, :remarks, :photo)";

        $stmt = oci_parse($db->getConnection(), $sql);

        oci_bind_by_name($stmt, ':employee_name', $employeeName);
        oci_bind_by_name($stmt, ':gender', $gender);
        oci_bind_by_name($stmt, ':birthdate', $birthdateForDB);
        oci_bind_by_name($stmt, ':job_id', $jobId);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':phone', $phoneForDB);
        oci_bind_by_name($stmt, ':salary', $salary);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        oci_bind_by_name($stmt, ':photo', $photoFilename);

        if (oci_execute($stmt)) {
            setFlashMessage('Employee created successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error creating employee: ' . $error['message'], 'danger');
        }

        oci_free_statement($stmt);

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }

    header('Location: employees.php');
    exit();
}

function updateEmployee($data, $files)
{
    try {
        // Validate required fields
        if (empty($data['employee_name']) || empty($data['job_id']) || empty($data['salary']) || empty($data['employee_id'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }

        // Extract and validate data
        $employeeId = (int) $data['employee_id'];
        $employeeName = trim($data['employee_name']);
        $gender = trim($data['gender'] ?? '');
        $birthdate = trim($data['birthdate'] ?? '');
        $jobId = (int) $data['job_id'];
        $address = trim($data['address'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $salary = (int) ($data['salary'] ?? 0);
        $remarks = trim($data['remarks'] ?? '');

        // Validation
        if (strlen($employeeName) > 50) {
            setFlashMessage('Employee Name must be 50 characters or less.', 'danger');
            return;
        }

        if (!empty($phone) && strlen($phone) > 15) {
            setFlashMessage('Phone number must be 15 characters or less.', 'danger');
            return;
        }

        if ($salary <= 0) {
            setFlashMessage('Salary must be greater than 0.', 'danger');
            return;
        }

        $db = Database::getInstance();

        // Validate salary range against job
        $jobSql = "SELECT MIN_SALARY, MAX_SALARY, JOB_TITLE FROM JOBS WHERE JOB_ID = :job_id";
        $jobStmt = oci_parse($db->getConnection(), $jobSql);
        oci_bind_by_name($jobStmt, ':job_id', $jobId);
        oci_execute($jobStmt);
        $jobResult = oci_fetch_assoc($jobStmt);
        oci_free_statement($jobStmt);

        if (!$jobResult) {
            setFlashMessage('Invalid job selected.', 'danger');
            return;
        }

        if ($salary < $jobResult['MIN_SALARY'] || $salary > $jobResult['MAX_SALARY']) {
            setFlashMessage("Salary must be between $" . number_format($jobResult['MIN_SALARY']) . 
                          " and $" . number_format($jobResult['MAX_SALARY']) . 
                          " for " . $jobResult['JOB_TITLE'] . ".", 'danger');
            return;
        }

        // Check if phone number already exists (excluding current employee)
        if (!empty($phone)) {
            $checkPhoneSql = "SELECT COUNT(*) as count FROM Employees 
                             WHERE PHONE = :phone AND EMPLOYEEID != :employee_id";
            $checkPhoneStmt = oci_parse($db->getConnection(), $checkPhoneSql);
            oci_bind_by_name($checkPhoneStmt, ':phone', $phone);
            oci_bind_by_name($checkPhoneStmt, ':employee_id', $employeeId);
            oci_execute($checkPhoneStmt);
            $phoneResult = oci_fetch_assoc($checkPhoneStmt);
            oci_free_statement($checkPhoneStmt);

            if ($phoneResult && $phoneResult['COUNT'] > 0) {
                setFlashMessage('Phone number already exists.', 'danger');
                return;
            }
        }

        // Handle photo upload if provided
        $photoFilename = null;
        $updatePhoto = false;
        if (isset($files['employee_photo']) && $files['employee_photo']['error'] === UPLOAD_ERR_OK) {
            // Get old photo filename to delete it later
            $oldPhotoSql = "SELECT PHOTO FROM Employees WHERE EMPLOYEEID = :employee_id";
            $oldPhotoStmt = oci_parse($db->getConnection(), $oldPhotoSql);
            oci_bind_by_name($oldPhotoStmt, ':employee_id', $employeeId);
            oci_execute($oldPhotoStmt);
            $oldPhotoResult = oci_fetch_assoc($oldPhotoStmt);
            oci_free_statement($oldPhotoStmt);

            $photoFilename = processPhotoUpload($files['employee_photo'], 'emp_' . $employeeId . '_' . time());
            $updatePhoto = true;

            // Delete old photo file if it exists
            if ($oldPhotoResult && $oldPhotoResult['PHOTO'] && !empty($oldPhotoResult['PHOTO'])) {
                $oldPhotoPath = 'uploads/employees/' . $oldPhotoResult['PHOTO'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
        }

        // Convert empty values to null
        $birthdateForDB = empty($birthdate) ? null : $birthdate;
        $phoneForDB = empty($phone) ? null : $phone;

        // Update employee
        if ($updatePhoto) {
            $sql = "UPDATE Employees 
                    SET EMPLOYEENAME = :employee_name, GENDER = :gender, 
                        BIRTHDATE = TO_DATE(:birthdate, 'YYYY-MM-DD'), JOB_ID = :job_id,
                        ADDRESS = :address, PHONE = :phone, SALARY = :salary,
                        REMARKS = :remarks, PHOTO = :photo
                    WHERE EMPLOYEEID = :employee_id";
        } else {
            $sql = "UPDATE Employees 
                    SET EMPLOYEENAME = :employee_name, GENDER = :gender, 
                        BIRTHDATE = TO_DATE(:birthdate, 'YYYY-MM-DD'), JOB_ID = :job_id,
                        ADDRESS = :address, PHONE = :phone, SALARY = :salary,
                        REMARKS = :remarks
                    WHERE EMPLOYEEID = :employee_id";
        }

        $stmt = oci_parse($db->getConnection(), $sql);

        oci_bind_by_name($stmt, ':employee_name', $employeeName);
        oci_bind_by_name($stmt, ':gender', $gender);
        oci_bind_by_name($stmt, ':birthdate', $birthdateForDB);
        oci_bind_by_name($stmt, ':job_id', $jobId);
        oci_bind_by_name($stmt, ':address', $address);
        oci_bind_by_name($stmt, ':phone', $phoneForDB);
        oci_bind_by_name($stmt, ':salary', $salary);
        oci_bind_by_name($stmt, ':remarks', $remarks);
        oci_bind_by_name($stmt, ':employee_id', $employeeId);

        if ($updatePhoto) {
            oci_bind_by_name($stmt, ':photo', $photoFilename);
        }

        if (oci_execute($stmt)) {
            setFlashMessage('Employee updated successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error updating employee: ' . $error['message'], 'danger');
        }

        oci_free_statement($stmt);

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }

    header('Location: employees.php');
    exit();
}

function processPhotoUpload($file, $employeePrefix)
{
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid photo type. Only JPG, PNG, and GIF are allowed.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('Photo size too large. Maximum size is 5MB.');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/employees/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $employeePrefix . '.' . strtolower($extension);
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload photo file.');
    }

    return $filename;
}

function getEmployeePhotoPath($employeeId)
{
    $db = Database::getInstance();

    $sql = "SELECT PHOTO FROM Employees WHERE EMPLOYEEID = :employee_id";
    $stmt = oci_parse($db->getConnection(), $sql);
    oci_bind_by_name($stmt, ':employee_id', $employeeId);
    oci_execute($stmt);

    $result = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if ($result && $result['PHOTO'] && !empty($result['PHOTO'])) {
        $photoPath = 'uploads/employees/' . $result['PHOTO'];

        if (file_exists($photoPath)) {
            return 'uploads/employees/' . $result['PHOTO'];
        }
    }
    return 'uploads/empty.png';
}

function formatDate($oracleDate)
{
    if (empty($oracleDate)) return '';
    
    try {
        $date = DateTime::createFromFormat('d-M-y', $oracleDate);
        if ($date) {
            return $date->format('Y-m-d');
        }
        
        $date = DateTime::createFromFormat('Y-m-d', $oracleDate);
        if ($date) {
            return $date->format('Y-m-d');
        }
        
        return $oracleDate;
    } catch (Exception $e) {
        return $oracleDate;
    }
}

// Data already fetched at the top of the file
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Employees Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#employeeModal">
                        <i class="fas fa-plus me-1"></i>Add Employee
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($employees)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Employees Found</h5>
                            <p class="text-muted">Start by adding your first employee.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#employeeModal">
                                <i class="fas fa-plus me-1"></i>
                                Add Employee
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Job Title</th>
                                        <th>Salary</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= getEmployeePhotoPath(htmlspecialchars($employee['EMPLOYEEID'])) ?>"
                                                    alt="Employee Photo"
                                                    style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td><?= htmlspecialchars($employee['EMPLOYEEID']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($employee['EMPLOYEENAME']) ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($employee['JOB_TITLE'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>$<?= number_format($employee['SALARY']) ?></td>
                                            <td><?= htmlspecialchars($employee['PHONE'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php if ($employee['GENDER']): ?>
                                                    <span class="badge <?= $employee['GENDER'] === 'Male' ? 'bg-primary' : 'bg-success' ?>">
                                                        <?= htmlspecialchars($employee['GENDER']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="editEmployee('<?= htmlspecialchars($employee['EMPLOYEEID'], ENT_QUOTES) ?>')"
                                                        data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmDelete('<?= htmlspecialchars($employee['EMPLOYEEID'], ENT_QUOTES) ?>', '<?= htmlspecialchars($employee['EMPLOYEENAME'], ENT_QUOTES) ?>')"
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

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalLabel"><?= $editEmployee ? 'Edit Employee' : 'Add Employee' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="employeeForm" method="POST" action="employees.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="<?= $editEmployee ? 'update' : 'create' ?>">
                    <input type="hidden" name="employee_id"
                        value="<?= $editEmployee ? htmlspecialchars($editEmployee['EMPLOYEEID']) : '' ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_name" class="form-label">Employee Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="employee_name" name="employee_name" maxlength="50"
                                    value="<?= $editEmployee ? htmlspecialchars($editEmployee['EMPLOYEENAME']) : '' ?>"
                                    required>
                                <div class="form-text">Full name of the employee (max 50 characters)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?= ($editEmployee && $editEmployee['GENDER'] === 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($editEmployee && $editEmployee['GENDER'] === 'Female') ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= ($editEmployee && $editEmployee['GENDER'] === 'Other') ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="birthdate" class="form-label">Birth Date</label>
                                <input type="date" class="form-control" id="birthdate" name="birthdate"
                                    value="<?= $editEmployee ? formatDate($editEmployee['BIRTHDATE']) : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" maxlength="15"
                                    value="<?= $editEmployee ? htmlspecialchars($editEmployee['PHONE']) : '' ?>">
                                <div class="form-text">Phone number (max 15 characters)</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="job_id" class="form-label">Job Title <span class="text-danger">*</span></label>
                                <select class="form-select" id="job_id" name="job_id" required onchange="updateSalaryRange()">
                                    <option value="">Select Job</option>
                                    <?php foreach ($jobs as $job): ?>
                                        <option value="<?= $job['JOB_ID'] ?>" 
                                                data-min-salary="<?= $job['MIN_SALARY'] ?>"
                                                data-max-salary="<?= $job['MAX_SALARY'] ?>"
                                                <?= ($editEmployee && $editEmployee['JOB_ID'] == $job['JOB_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($job['JOB_TITLE']) ?> 
                                            ($<?= number_format($job['MIN_SALARY']) ?> - $<?= number_format($job['MAX_SALARY']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="salary" class="form-label">Salary <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="salary" name="salary" min="1"
                                    value="<?= $editEmployee ? htmlspecialchars($editEmployee['SALARY']) : '' ?>"
                                    required>
                                <div class="form-text" id="salary-range-text">Select a job to see salary range</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" maxlength="150"><?= $editEmployee ? htmlspecialchars($editEmployee['ADDRESS']) : '' ?></textarea>
                        <div class="form-text">Employee address (max 150 characters)</div>
                    </div>

                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" maxlength="50"><?= $editEmployee ? htmlspecialchars($editEmployee['REMARKS']) : '' ?></textarea>
                        <div class="form-text">Additional notes (max 50 characters)</div>
                    </div>

                    <div class="mb-3">
                        <label for="employee_photo" class="form-label">Employee Photo</label>
                        <input type="file" class="form-control" id="employee_photo" name="employee_photo"
                            accept="image/jpeg,image/jpg,image/png,image/gif">
                        <div class="form-text">Optional. Max 5MB. Supported formats: JPG, PNG, GIF</div>
                        <div id="imagePreview" class="mt-2"
                            style="<?= ($editEmployee && $editEmployee['PHOTO']) ? 'display: block;' : 'display: none;' ?>">
                            <img id="previewImg"
                                src="<?= ($editEmployee && $editEmployee['PHOTO']) ? getEmployeePhotoPath($editEmployee['EMPLOYEEID']) : '' ?>"
                                alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <?= $editEmployee ? 'Update Employee' : 'Create Employee' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editEmployee): ?>
    <script>
        // Show modal for edit mode
        document.addEventListener('DOMContentLoaded', function () {
            var modal = new bootstrap.Modal(document.getElementById('employeeModal'));
            modal.show();
        });
    </script>
<?php endif; ?>

<script>
    function resetForm() {
        document.getElementById('employeeForm').reset();
        document.getElementById('employeeModalLabel').textContent = 'Add Employee';
        document.querySelector('input[name="action"]').value = 'create';
        document.querySelector('input[name="employee_id"]').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        updateSalaryRange();
    }

    function editEmployee(employeeId) {
        window.location.href = `employees.php?action=edit&id=${encodeURIComponent(employeeId)}`;
    }

    function confirmDelete(employeeId, employeeName) {
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete employee "${employeeName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `employees.php?action=delete&employee_id=${encodeURIComponent(employeeId)}`;
            }
        });
    }

    function updateSalaryRange() {
        const jobSelect = document.getElementById('job_id');
        const salaryInput = document.getElementById('salary');
        const salaryRangeText = document.getElementById('salary-range-text');
        
        if (jobSelect.value) {
            const selectedOption = jobSelect.options[jobSelect.selectedIndex];
            const minSalary = selectedOption.dataset.minSalary;
            const maxSalary = selectedOption.dataset.maxSalary;
            
            salaryInput.min = minSalary;
            salaryInput.max = maxSalary;
            
            salaryRangeText.textContent = `Salary range: $${Number(minSalary).toLocaleString()} - $${Number(maxSalary).toLocaleString()}`;
            salaryRangeText.className = 'form-text text-info';
        } else {
            salaryInput.removeAttribute('min');
            salaryInput.removeAttribute('max');
            salaryRangeText.textContent = 'Select a job to see salary range';
            salaryRangeText.className = 'form-text';
        }
    }

    // Image preview
    function handleImagePreview(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('imagePreview').style.display = 'none';
        }
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize salary range on page load if editing
        updateSalaryRange();

        // Image preview on change
        const imageInput = document.getElementById('employee_photo');
        if (imageInput) {
            imageInput.addEventListener('change', function () {
                handleImagePreview(this);
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
