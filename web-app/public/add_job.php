<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$success_message = '';
$error_message = '';

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
        
        // Insert new job
        $sql = "INSERT INTO JOBS (JOB_TITLE, MIN_SALARY, MAX_SALARY) 
                VALUES (:job_title, :min_salary, :max_salary)";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':job_title', $job_title);
        oci_bind_by_name($stmt, ':min_salary', $min_salary);
        oci_bind_by_name($stmt, ':max_salary', $max_salary);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Job created successfully!";
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
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>Add New Job
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

                    <form method="POST" action="add_job.php">
                        <div class="mb-4">
                            <label for="job_title" class="form-label">
                                <i class="fas fa-user-tie me-1"></i>Job Title *
                            </label>
                            <input type="text" class="form-control" id="job_title" name="job_title" 
                                   value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>" 
                                   required maxlength="50" placeholder="e.g., Sales Manager, Software Developer">
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
                                           value="<?php echo htmlspecialchars($_POST['min_salary'] ?? ''); ?>" 
                                           min="0" step="1000" placeholder="0" onchange="validateSalaryRange()">
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
                                           value="<?php echo htmlspecialchars($_POST['max_salary'] ?? ''); ?>" 
                                           min="0" step="1000" placeholder="0" onchange="validateSalaryRange()">
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
                                                Enter salary values to see range
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

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-1"></i>Job Creation Guidelines:</h6>
                            <ul class="mb-0">
                                <li><strong>Job Title:</strong> Use clear, professional titles that employees and candidates will understand</li>
                                <li><strong>Salary Range:</strong> Both minimum and maximum salaries are optional but recommended for transparency</li>
                                <li><strong>Range Validation:</strong> Maximum salary must be greater than or equal to minimum salary</li>
                                <li><strong>Future Use:</strong> This job will be available when creating or editing employee records</li>
                            </ul>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="jobs.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Jobs
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Create Job
                            </button>
                        </div>
                    </form>
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
        rangeDisplay.innerHTML = 'Enter salary values to see range';
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