<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Count total jobs for pagination
    $count_sql = "SELECT COUNT(*) as total FROM JOBS";
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "JOB_TITLE LIKE :search";
        $params[':search'] = '%' . $search . '%';
        $count_sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    $total_count = DatabaseOCI8::queryOne($count_sql, $params);
    $total_jobs = $total_count['TOTAL'];
    $total_pages = ceil($total_jobs / $per_page);
    
    // Get jobs with pagination
    $sql = "SELECT * FROM (
        SELECT j.*, ROW_NUMBER() OVER (ORDER BY j.JOB_TITLE) as rn
        FROM JOBS j";
    
    if (!empty($where_conditions)) {
        $sql .= " WHERE " . implode(' AND ', $where_conditions);
    }
    
    $sql .= ") WHERE rn > :offset AND rn <= :limit";
    $params[':offset'] = $offset;
    $params[':limit'] = $offset + $per_page;
    
    $jobs = DatabaseOCI8::query($sql, $params);
    
} catch (Exception $e) {
    $error_message = "Error loading jobs: " . $e->getMessage();
    $jobs = [];
    $total_jobs = 0;
    $total_pages = 0;
}
?>

<!-- Jobs Management Page -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>Jobs Management
                    </h4>
                    <a href="add_job.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i>Add Job
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="jobs.php" class="d-flex">
                                <input type="text" class="form-control me-2" name="search" 
                                       placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="jobs.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info fs-6">
                                <?php if ($search): ?>
                                    <?php echo count($jobs); ?> results for "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    Total: <?php echo number_format($total_jobs); ?> jobs
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th>Job ID</th>
                            <th>Job Title</th>
                            <th>Salary Range</th>
                            <th>Min Salary</th>
                            <th>Max Salary</th>
                            <th>Employee Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($job['JOB_ID']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($job['JOB_TITLE']); ?></strong>
                                    </td>
                                    <td>
                                        <?php 
                                        $min = $job['MIN_SALARY'] ?? 0;
                                        $max = $job['MAX_SALARY'] ?? 0;
                                        if ($min > 0 && $max > 0): 
                                        ?>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: 100%;">
                                                    $<?php echo number_format($min); ?> - $<?php echo number_format($max); ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($job['MIN_SALARY']): ?>
                                            <span class="text-success fw-bold">
                                                $<?php echo number_format($job['MIN_SALARY']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($job['MAX_SALARY']): ?>
                                            <span class="text-primary fw-bold">
                                                $<?php echo number_format($job['MAX_SALARY']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        try {
                                            $emp_count = DatabaseOCI8::queryOne(
                                                "SELECT COUNT(*) as count FROM Employees WHERE JOB_ID = :job_id", 
                                                [':job_id' => $job['JOB_ID']]
                                            );
                                            $count = $emp_count['COUNT'] ?? 0;
                                        } catch (Exception $e) {
                                            $count = 0;
                                        }
                                        ?>
                                        <?php if ($count > 0): ?>
                                            <span class="badge bg-info">
                                                <?php echo $count; ?> employee<?php echo $count != 1 ? 's' : ''; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No employees</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="edit_job.php?id=<?php echo $job['JOB_ID']; ?>" 
                                               class="btn btn-outline-primary" 
                                               data-bs-toggle="tooltip" title="Edit Job">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-info" 
                                                    title="View Details" onclick="viewJobDetails(<?php echo $job['JOB_ID']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($count == 0): ?>
                                                <button type="button" class="btn btn-outline-danger" 
                                                        title="Delete Job" onclick="deleteJob(<?php echo $job['JOB_ID']; ?>, '<?php echo htmlspecialchars($job['JOB_TITLE']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        title="Cannot delete - has employees" disabled>
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                    <div class="text-muted">
                                        <?php if ($search): ?>
                                            No jobs found matching your search criteria.
                                        <?php else: ?>
                                            No jobs found. <a href="add_job.php">Add the first job</a>.
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Jobs pagination">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Job Details Modal -->
<div class="modal fade" id="jobDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Job Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="jobDetailsContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewJobDetails(jobId) {
    const modal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
    const content = document.getElementById('jobDetailsContent');
    
    content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    modal.show();
    
    // Load job details via AJAX (simplified version)
    fetch(`job_details.php?id=${jobId}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">Error loading job details.</div>';
        });
}

function deleteJob(jobId, jobTitle) {
    if (confirm(`Are you sure you want to delete the job "${jobTitle}"?\n\nThis action cannot be undone.`)) {
        window.location.href = `delete_job.php?id=${jobId}`;
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once './includes/footer.php'; ?>