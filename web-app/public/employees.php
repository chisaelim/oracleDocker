<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';

// Search and pagination parameters
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $connection = DatabaseOCI8::getConnection();
    
    // Build search condition
    $search_condition = '';
    $search_params = [];
    if (!empty($search)) {
        $search_condition = " WHERE UPPER(e.EMPLOYEENAME) LIKE UPPER(:search) 
                             OR UPPER(j.JOB_TITLE) LIKE UPPER(:search)
                             OR UPPER(e.PHONE) LIKE UPPER(:search)";
        $search_params[':search'] = '%' . $search . '%';
    }
    
    // Count total employees
    $count_sql = "SELECT COUNT(*) as count 
                  FROM Employees e 
                  LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID" . $search_condition;
    
    $stmt = oci_parse($connection, $count_sql);
    foreach ($search_params as $param => $value) {
        oci_bind_by_name($stmt, $param, $value);
    }
    oci_execute($stmt);
    $result = oci_fetch_assoc($stmt);
    $total_employees = $result['COUNT'];
    $total_pages = ceil($total_employees / $per_page);
    oci_free_statement($stmt);
    
    // Get employees with pagination
    $sql = "SELECT * FROM (
                SELECT e.EMPLOYEEID, e.EMPLOYEENAME, e.GENDER, e.BIRTHDATE, 
                       e.ADDRESS, e.PHONE, e.SALARY, e.REMARKS,
                       j.JOB_TITLE,
                       ROW_NUMBER() OVER (ORDER BY e.EMPLOYEEID) as rn
                FROM Employees e 
                LEFT JOIN JOBS j ON e.JOB_ID = j.JOB_ID" . 
                $search_condition . "
            ) WHERE rn > :offset AND rn <= :end_row";
    
    $stmt = oci_parse($connection, $sql);
    foreach ($search_params as $param => $value) {
        oci_bind_by_name($stmt, $param, $value);
    }
    oci_bind_by_name($stmt, ':offset', $offset);
    $end_row = $offset + $per_page;
    oci_bind_by_name($stmt, ':end_row', $end_row);
    
    oci_execute($stmt);
    
    $employees = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $employees[] = $row;
    }
    oci_free_statement($stmt);
    
} catch (Exception $e) {
    $error_message = "Error loading employees: " . $e->getMessage();
    $employees = [];
    $total_employees = 0;
    $total_pages = 0;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>Employees Management
                    </h4>
                    <a href="add_employee.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i>Add Employee
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="employees.php" class="d-flex">
                                <input type="text" class="form-control me-2" name="search" 
                                       placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="employees.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Showing <?php echo count($employees); ?> of <?php echo $total_employees; ?> employees
                                <?php if (!empty($search)): ?>
                                    for "<?php echo htmlspecialchars($search); ?>"
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Employees Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Job Title</th>
                                    <th>Gender</th>
                                    <th>Phone</th>
                                    <th>Salary</th>
                                    <th>Birthdate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                            <div class="text-muted">
                                                <?php echo !empty($search) ? 'No employees found matching your search.' : 'No employees available.'; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($employee['EMPLOYEEID'] ?? ''); ?></strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($employee['EMPLOYEENAME'] ?? ''); ?></div>
                                                        <?php if (!empty($employee['ADDRESS'])): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($employee['ADDRESS']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($employee['JOB_TITLE'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($employee['GENDER'])): ?>
                                                    <span class="badge <?php echo $employee['GENDER'] == 'Male' ? 'bg-primary' : 'bg-success'; ?>">
                                                        <?php echo htmlspecialchars($employee['GENDER']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($employee['PHONE'] ?? ''); ?></td>
                                            <td class="text-end">
                                                <?php if (!empty($employee['SALARY'])): ?>
                                                    <strong><?php echo Utils::formatCurrency($employee['SALARY']); ?></strong>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($employee['BIRTHDATE'])) {
                                                    $birthdate = new DateTime($employee['BIRTHDATE']);
                                                    echo $birthdate->format('M d, Y');
                                                    
                                                    // Calculate age
                                                    $today = new DateTime();
                                                    $age = $today->diff($birthdate)->y;
                                                    echo '<br><small class="text-muted">Age: ' . $age . '</small>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            title="Edit Employee">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-info" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            title="Delete Employee">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Employees pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
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

<?php require_once './includes/footer.php'; ?>