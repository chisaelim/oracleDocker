<?php
// jobs.php - Jobs Management with CRUD Operations
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
$editJob = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editJob = getJobById($_GET['id']);
}

// Get all jobs for display
$jobs = getAllJobs();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>Jobs Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#jobModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i>Add Job
                    </button>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($jobs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Jobs Found</h5>
                            <p class="text-muted">Start by adding your first job position.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#jobModal" onclick="resetForm()">
                                <i class="fas fa-plus me-1"></i>
                                Add Job
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="jobsTable" class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Job ID</th>
                                        <th>Job Title</th>
                                        <th>Min Salary</th>
                                        <th>Max Salary</th>
                                        <th>Salary Range</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($job['JOB_ID']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($job['JOB_TITLE']); ?></strong></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    $<?php echo number_format($job['MIN_SALARY']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    $<?php echo number_format($job['MAX_SALARY']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;" 
                                                         data-bs-toggle="tooltip" title="Range: $<?php echo number_format($job['MAX_SALARY'] - $job['MIN_SALARY']); ?>">
                                                        $<?php echo number_format($job['MAX_SALARY'] - $job['MIN_SALARY']); ?> range
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editJob(<?php echo htmlspecialchars(json_encode($job)); ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#jobModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $job['JOB_ID']; ?>, '<?php echo htmlspecialchars($job['JOB_TITLE']); ?>')">
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

<!-- Job Modal -->
<div class="modal fade" id="jobModal" tabindex="-1" aria-labelledby="jobModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobModalLabel">Add New Job</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="jobForm">
                <div class="modal-body">
                    <input type="hidden" id="jobId" name="id">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="jobTitle" class="form-label">Job Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="jobTitle" name="job_title" required maxlength="50">
                        <div class="form-text">Maximum 50 characters</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="minSalary" class="form-label">Minimum Salary <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="minSalary" name="min_salary" 
                                           required min="0" max="99999999" step="1" onchange="validateSalaryRange()">
                                </div>
                                <div class="form-text">Enter minimum salary (0-99,999,999)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="maxSalary" class="form-label">Maximum Salary <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="maxSalary" name="max_salary" 
                                           required min="0" max="99999999" step="1" onchange="validateSalaryRange()">
                                </div>
                                <div class="form-text">Enter maximum salary (0-99,999,999)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="salaryRangeInfo" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Salary Range:</strong> <span id="salaryRangeText"></span>
                    </div>
                    
                    <div class="alert alert-warning" id="salaryWarning" style="display: none;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <span id="salaryWarningText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-1"></i>Save Job
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>

// Reset form for new job
function resetForm() {
    document.getElementById('jobForm').reset();
    document.getElementById('jobId').value = '';
    document.getElementById('formAction').value = 'create';
    document.getElementById('jobModalLabel').textContent = 'Add New Job';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Job';
    document.getElementById('salaryRangeInfo').style.display = 'none';
    document.getElementById('salaryWarning').style.display = 'none';
}

// Edit job function
function editJob(job) {
    document.getElementById('jobId').value = job.JOB_ID;
    document.getElementById('formAction').value = 'update';
    document.getElementById('jobTitle').value = job.JOB_TITLE;
    document.getElementById('minSalary').value = job.MIN_SALARY;
    document.getElementById('maxSalary').value = job.MAX_SALARY;
    
    document.getElementById('jobModalLabel').textContent = 'Edit Job';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Update Job';
    
    validateSalaryRange();
}

// Validate salary range
function validateSalaryRange() {
    const minSalary = parseFloat(document.getElementById('minSalary').value) || 0;
    const maxSalary = parseFloat(document.getElementById('maxSalary').value) || 0;
    const salaryRangeInfo = document.getElementById('salaryRangeInfo');
    const salaryWarning = document.getElementById('salaryWarning');
    const salaryRangeText = document.getElementById('salaryRangeText');
    const salaryWarningText = document.getElementById('salaryWarningText');
    const submitBtn = document.getElementById('submitBtn');
    
    // Hide alerts initially
    salaryRangeInfo.style.display = 'none';
    salaryWarning.style.display = 'none';
    
    if (minSalary > 0 && maxSalary > 0) {
        if (maxSalary > minSalary) {
            // Valid range
            const range = maxSalary - minSalary;
            salaryRangeText.textContent = `$${minSalary.toLocaleString()} - $${maxSalary.toLocaleString()} (Range: $${range.toLocaleString()})`;
            salaryRangeInfo.style.display = 'block';
            submitBtn.disabled = false;
        } else if (maxSalary <= minSalary) {
            // Invalid range
            salaryWarningText.textContent = 'Maximum salary must be greater than minimum salary.';
            salaryWarning.style.display = 'block';
            submitBtn.disabled = true;
        }
    } else {
        submitBtn.disabled = false;
    }
}

// Confirm delete
function confirmDelete(jobId, jobTitle) {
    Swal.fire({
        title: 'Confirm Deletion',
        text: `Are you sure you want to delete the job "${jobTitle}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `jobs.php?action=delete&id=${jobId}`;
        }
    });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

</script>

<?php require_once 'includes/footer.php';

// PHP Functions

function getAllJobs() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT JOB_ID, JOB_TITLE, MIN_SALARY, MAX_SALARY 
                FROM JOBS 
                ORDER BY JOB_TITLE";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_execute($stmt);
        
        $jobs = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $jobs[] = $row;
        }
        
        oci_free_statement($stmt);
        return $jobs;
    } catch (Exception $e) {
        setFlashMessage("Error loading jobs: " . $e->getMessage(), 'danger');
        return [];
    }
}

function getJobById($id) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT JOB_ID, JOB_TITLE, MIN_SALARY, MAX_SALARY 
                FROM JOBS 
                WHERE JOB_ID = :id";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
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
        createJob($_POST);
    } elseif ($action === 'update') {
        updateJob($_POST);
    }
}

function createJob($data) {
    try {
        // Validate required fields
        if (empty($data['job_title']) || empty($data['min_salary']) || empty($data['max_salary'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        // Validate field lengths and formats
        if (strlen($data['job_title']) > 50) {
            setFlashMessage('Job title must be 50 characters or less.', 'danger');
            return;
        }
        
        // Validate salary values (NUMBER(8) means max 99,999,999)
        if (!is_numeric($data['min_salary']) || !is_numeric($data['max_salary'])) {
            setFlashMessage('Salary values must be numeric.', 'danger');
            return;
        }
        
        $minSalary = floatval($data['min_salary']);
        $maxSalary = floatval($data['max_salary']);
        
        if ($minSalary < 0 || $maxSalary < 0) {
            setFlashMessage('Salary values cannot be negative.', 'danger');
            return;
        }
        
        if ($minSalary > 99999999 || $maxSalary > 99999999) {
            setFlashMessage('Salary values cannot exceed 99,999,999.', 'danger');
            return;
        }
        
        // Validate salary range constraint
        if ($maxSalary <= $minSalary) {
            setFlashMessage('Maximum salary must be greater than minimum salary.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        // Check if job title already exists (case-insensitive)
        $checkSql = "SELECT COUNT(*) as count FROM JOBS WHERE UPPER(JOB_TITLE) = UPPER(:job_title)";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        $job_title_check = $data['job_title'];
        oci_bind_by_name($checkStmt, ':job_title', $job_title_check);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        
        if ($row && $row['COUNT'] > 0) {
            setFlashMessage('A job with this title already exists. Please choose a different title.', 'danger');
            oci_free_statement($checkStmt);
            return;
        }
        
        oci_free_statement($checkStmt);
        
        $sql = "INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) 
                VALUES (:job_title, :min_salary, :max_salary)";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        // Extract values to variables for binding by reference
        $job_title = $data['job_title'];
        $min_salary = $minSalary;
        $max_salary = $maxSalary;
        
        oci_bind_by_name($stmt, ':job_title', $job_title);
        oci_bind_by_name($stmt, ':min_salary', $min_salary);
        oci_bind_by_name($stmt, ':max_salary', $max_salary);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            throw new Exception('Database Error: ' . $error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Job created successfully!', 'success');
        header('Location: jobs.php');
        exit;
        
    } catch (Exception $e) {
        setFlashMessage('Error creating job: ' . $e->getMessage(), 'danger');
    }
}

function updateJob($data) {
    try {
        // Validate required fields
        if (empty($data['job_title']) || empty($data['min_salary']) || empty($data['max_salary']) || empty($data['id'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }
        
        // Validate field lengths and formats
        if (strlen($data['job_title']) > 50) {
            setFlashMessage('Job title must be 50 characters or less.', 'danger');
            return;
        }
        
        // Validate salary values (NUMBER(8) means max 99,999,999)
        if (!is_numeric($data['min_salary']) || !is_numeric($data['max_salary'])) {
            setFlashMessage('Salary values must be numeric.', 'danger');
            return;
        }
        
        $minSalary = floatval($data['min_salary']);
        $maxSalary = floatval($data['max_salary']);
        
        if ($minSalary < 0 || $maxSalary < 0) {
            setFlashMessage('Salary values cannot be negative.', 'danger');
            return;
        }
        
        if ($minSalary > 99999999 || $maxSalary > 99999999) {
            setFlashMessage('Salary values cannot exceed 99,999,999.', 'danger');
            return;
        }
        
        // Validate salary range constraint
        if ($maxSalary <= $minSalary) {
            setFlashMessage('Maximum salary must be greater than minimum salary.', 'danger');
            return;
        }
        
        $db = Database::getInstance();
        
        // Check if job title already exists (excluding current job)
        $checkSql = "SELECT COUNT(*) as count FROM JOBS WHERE UPPER(JOB_TITLE) = UPPER(:job_title) AND JOB_ID != :id";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        $job_title_check = $data['job_title'];
        $id_check = $data['id'];
        oci_bind_by_name($checkStmt, ':job_title', $job_title_check);
        oci_bind_by_name($checkStmt, ':id', $id_check);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        
        if ($row && $row['COUNT'] > 0) {
            setFlashMessage('A job with this title already exists. Please choose a different title.', 'danger');
            oci_free_statement($checkStmt);
            return;
        }
        
        oci_free_statement($checkStmt);
        
        $sql = "UPDATE JOBS SET JOB_TITLE = :job_title, MIN_SALARY = :min_salary, MAX_SALARY = :max_salary 
                WHERE JOB_ID = :id";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        // Extract values to variables for binding by reference
        $job_title = $data['job_title'];
        $min_salary = $minSalary;
        $max_salary = $maxSalary;
        $id = $data['id'];
        
        oci_bind_by_name($stmt, ':job_title', $job_title);
        oci_bind_by_name($stmt, ':min_salary', $min_salary);
        oci_bind_by_name($stmt, ':max_salary', $max_salary);
        oci_bind_by_name($stmt, ':id', $id);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            throw new Exception('Database Error: ' . $error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Job updated successfully!', 'success');
        header('Location: jobs.php');
        exit;
        
    } catch (Exception $e) {
        setFlashMessage('Error updating job: ' . $e->getMessage(), 'danger');
    }
}

function handleDelete($id) {
    try {
        $db = Database::getInstance();
        
        // Check if job is being used by any employees
        $checkSql = "SELECT COUNT(*) as count FROM EMPLOYEES WHERE JOB_ID = :id";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        $id_param = $id;
        oci_bind_by_name($checkStmt, ':id', $id_param);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);
        
        if ($row && $row['COUNT'] > 0) {
            setFlashMessage('Cannot delete job as it is assigned to existing employees.', 'danger');
            oci_free_statement($checkStmt);
            header('Location: jobs.php');
            exit;
        }
        
        oci_free_statement($checkStmt);
        
        // Delete the job
        $deleteSql = "DELETE FROM JOBS WHERE JOB_ID = :id";
        $deleteStmt = oci_parse($db->getConnection(), $deleteSql);
        $delete_id = $id;
        oci_bind_by_name($deleteStmt, ':id', $delete_id);
        
        if (!oci_execute($deleteStmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($deleteStmt);
            throw new Exception('Database Error: ' . $error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($deleteStmt);
        
        setFlashMessage('Job deleted successfully!', 'success');
        
    } catch (Exception $e) {
        setFlashMessage('Error deleting job: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: jobs.php');
    exit;
}
?>