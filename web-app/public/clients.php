<?php
$page_title = 'Clients';
require_once 'includes/header.php';
require_once 'includes/utils.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = AppConfig::ITEMS_PER_PAGE;
$offset = ($page - 1) * $per_page;

// Search
$search = isset($_GET['search']) ? Utils::sanitizeInput($_GET['search']) : '';

// Check if OCI8 extension is available and use appropriate database config
if (extension_loaded('oci8')) {
    require_once 'config/database_oci8.php';
    $use_oci8 = true;
} else {
    $use_oci8 = false;
}

try {
    if ($use_oci8) {
        $connection = DatabaseOCI8::getConnection();
        
        // Build query
        $where_clause = '';
        
        if ($search) {
            $where_clause = "WHERE UPPER(CLIENTNAME) LIKE UPPER('%" . $search . "%') OR UPPER(CITY) LIKE UPPER('%" . $search . "%')";
        }
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as count FROM Clients $where_clause";
        $result = DatabaseOCI8::queryOne($count_sql);
        $total_records = $result['COUNT'];
        $total_pages = ceil($total_records / $per_page);
        
        // Get clients with pagination
        $sql = "
            SELECT * FROM (
                SELECT c.*, ct.TYPE_NAME, 
                       ROW_NUMBER() OVER (ORDER BY c.CLIENT_NO DESC) as rn
                FROM Clients c
                LEFT JOIN Client_Type ct ON c.CLIENT_TYPE = ct.CLIENT_TYPE
                $where_clause
            ) WHERE rn > $offset AND rn <= " . ($offset + $per_page);
        
        $clients = DatabaseOCI8::query($sql);
    } else {
        Utils::setErrorMessage("OCI8 extension not available - cannot connect to Oracle database");
        $clients = [];
        $total_records = 0;
        $total_pages = 0;
    }
    
} catch (Exception $e) {
    Utils::setErrorMessage("Database error: " . $e->getMessage());
    $clients = [];
    $total_records = 0;
    $total_pages = 0;
}
?>

<!-- Clients Management Page -->
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Clients Management
                    </h4>
                    <a href="add_client.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i>Add Client
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="clients.php" class="d-flex">
                                <input type="text" class="form-control me-2" name="search" 
                                       placeholder="Search clients..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="clients.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-info fs-6">
                                Total: <?php echo number_format($total_records); ?> clients
                                <?php if ($search): ?>
                                    (filtered for "<?php echo htmlspecialchars($search); ?>")
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

<!-- Clients Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            Client List
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($clients)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Client Name</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Discount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($client['CLIENT_NO']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($client['CLIENTNAME']); ?></td>
                                <td><?php echo htmlspecialchars($client['ADDRESS'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($client['CITY'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($client['PHONE']); ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($client['PHONE']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($client['TYPE_NAME']): ?>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($client['TYPE_NAME']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($client['DISCOUNT']): ?>
                                        <span class="badge bg-success">
                                            <?php echo number_format($client['DISCOUNT'], 2); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">0%</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="view_client.php?id=<?php echo $client['CLIENT_NO']; ?>" 
                                           class="btn btn-outline-info" 
                                           data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_client.php?id=<?php echo $client['CLIENT_NO']; ?>" 
                                           class="btn btn-outline-primary" 
                                           data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_client.php?id=<?php echo $client['CLIENT_NO']; ?>" 
                                           class="btn btn-outline-danger btn-delete" 
                                           data-bs-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </div>
                    <?php 
                    $base_url = 'clients.php?1=1' . ($search ? '&search=' . urlencode($search) : '');
                    echo Utils::generatePagination($page, $total_pages, $base_url); 
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No Clients Found</h5>
                <?php if ($search): ?>
                    <p class="text-muted">No clients match your search criteria.</p>
                    <a href="clients.php" class="btn btn-outline-primary">View All Clients</a>
                <?php else: ?>
                    <p class="text-muted">Start by adding your first client.</p>
                    <a href="add_client.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Add First Client
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>