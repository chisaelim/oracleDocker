<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';
$job_id = $_GET['id'] ?? null;

if (!$job_id) {
    header('Location: jobs.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $job_title = trim($_POST['job_title']);
        $min_salary = floatval($_POST['min_salary'] ?: 0);
        $max_salary = floatval($_POST['max_salary'] ?: 0);
        
        // Validate required fields
        if (empty($job_title)) {
            throw new Exception("Job title is required.");
        }
        
        // Validate salary range
        if ($min_salary > 0 && $max_salary > 0 && $min_salary > $max_salary) {
            throw new Exception("Minimum salary cannot be greater than maximum salary.");
        }
        
        // Update job
        $sql = "UPDATE JOBS SET 
                JOB_TITLE = :job_title, 
                MIN_SALARY = :min_salary, 
                MAX_SALARY = :max_salary
                WHERE JOB_ID = :job_id";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':job_title', $job_title);
        oci_bind_by_name($stmt, ':min_salary', $min_salary);
        oci_bind_by_name($stmt, ':max_salary', $max_salary);
        oci_bind_by_name($stmt, ':job_id', $job_id);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Job updated successfully!";
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

// Get job data
try {
    $job = DatabaseOCI8::queryOne("SELECT * FROM JOBS WHERE JOB_ID = :job_id", [':job_id' => $job_id]);
    
    if (!$job) {
        throw new Exception("Job not found.");
    }
    
    // Get employee count for this job
    $emp_count = DatabaseOCI8::queryOne(
        "SELECT COUNT(*) as count FROM Employees WHERE JOB_ID = :job_id", 
        [':job_id' => $job_id]
    );
    $employee_count = $emp_count['COUNT'] ?? 0;
    
} catch (Exception $e) {
    $error_message = "Error loading job: " . $e->getMessage();
    $job = null;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>Edit Job
                        <?php if ($job): ?>
                            - <?php echo htmlspecialchars($job['JOB_TITLE']); ?>
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

                    <?php if ($job): ?>
                    <!-- Job Information Summary -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-3">
                                            <strong>Job ID:</strong> 
                                            <span class="badge bg-secondary"><?php echo $job['JOB_ID']; ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Employees:</strong>
                                            <?php if ($employee_count > 0): ?>
                                                <span class="badge bg-info">
                                                    <?php echo $employee_count; ?> assigned
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">None assigned</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if ($employee_count > 0): ?>
                                                <div class="alert alert-warning py-2 mb-0">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <small>This job has <?php echo $employee_count; ?> employee(s) assigned. Changes will affect their job classification.</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="edit_job.php?id=<?php echo urlencode($job_id); ?>">
                        <div class="mb-4">
                            <label for="job_title" class="form-label">
                                <i class="fas fa-user-tie me-1"></i>Job Title *
                            </label>
                            <input type="text" class="form-control" id="job_title" name="job_title" 
                                   value="<?php echo htmlspecialchars($_POST['job_title'] ?? $job['JOB_TITLE']); ?>" 
                                   required maxlength="50">
                            <div class="form-text">Enter a descriptive job title</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="min_salary" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Minimum Salary
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="min_salary" name="min_salary" 
                                           value="<?php echo htmlspecialchars($_POST['min_salary'] ?? $job['MIN_SALARY']); ?>" 
                                           min="0" step="1000" onchange="validateSalaryRange()">
                                </div>
                                <div class="form-text">Minimum annual salary for this position</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="max_salary" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Maximum Salary
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="max_salary" name="max_salary" 
                                           value="<?php echo htmlspecialchars($_POST['max_salary'] ?? $job['MAX_SALARY']); ?>" 
                                           min="0" step="1000" onchange="validateSalaryRange()">
                                </div>
                                <div class="form-text">Maximum annual salary for this position</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card bg-light">
                                <div class="card-body py-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">
                                                <i class="fas fa-chart-bar me-1"></i>Salary Range Preview
                                            </h6>
                                            <div id="salary_range_display" class="text-muted">
                                                Loading...
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div id="salary_difference" class="text-info fw-bold">
                                                <!-- Salary difference will be displayed here -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div id="salary_progress" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="jobs.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Jobs
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>Update Job
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <div class="text-muted">Job not found or could not be loaded.</div>
                            <a href="jobs.php" class="btn btn-primary mt-3">
                                <i class="fas fa-arrow-left me-1"></i>Back to Jobs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateSalaryRange() {
    const minSalary = parseFloat(document.getElementById('min_salary').value) || 0;
    const maxSalary = parseFloat(document.getElementById('max_salary').value) || 0;
    const rangeDisplay = document.getElementById('salary_range_display');
    const salaryDifference = document.getElementById('salary_difference');
    const progressBar = document.getElementById('salary_progress');
    
    if (minSalary > 0 || maxSalary > 0) {
        if (minSalary > 0 && maxSalary > 0) {
            if (minSalary <= maxSalary) {
                const difference = maxSalary - minSalary;
                rangeDisplay.innerHTML = `<span class="text-success">$${minSalary.toLocaleString()} - $${maxSalary.toLocaleString()}</span>`;
                salaryDifference.innerHTML = `Range: $${difference.toLocaleString()}`;
                salaryDifference.className = 'text-success fw-bold';
                progressBar.style.width = '100%';
                progressBar.className = 'progress-bar bg-success';
            } else {
                rangeDisplay.innerHTML = '<span class="text-danger">Invalid range: Min > Max</span>';
                salaryDifference.innerHTML = 'Error';
                salaryDifference.className = 'text-danger fw-bold';
                progressBar.style.width = '100%';
                progressBar.className = 'progress-bar bg-danger';
            }
        } else if (minSalary > 0) {
            rangeDisplay.innerHTML = `<span class="text-info">Minimum: $${minSalary.toLocaleString()}</span>`;
            salaryDifference.innerHTML = 'No max set';
            salaryDifference.className = 'text-info fw-bold';
            progressBar.style.width = '50%';
            progressBar.className = 'progress-bar bg-info';
        } else if (maxSalary > 0) {
            rangeDisplay.innerHTML = `<span class="text-warning">Maximum: $${maxSalary.toLocaleString()}</span>`;
            salaryDifference.innerHTML = 'No min set';
            salaryDifference.className = 'text-warning fw-bold';
            progressBar.style.width = '50%';
            progressBar.className = 'progress-bar bg-warning';
        }
    } else {
        rangeDisplay.innerHTML = 'No salary range specified';
        rangeDisplay.className = 'text-muted';
        salaryDifference.innerHTML = '';
        progressBar.style.width = '0%';
    }
}

// Initialize salary range display on page load
document.addEventListener('DOMContentLoaded', function() {
    validateSalaryRange();
});
</script>

<?php require_once './includes/footer.php'; ?>