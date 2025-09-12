<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';
require_once './includes/utils.php';

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
        $search_condition = " WHERE UPPER(p.PRODUCTNAME) LIKE UPPER(:search) 
                             OR UPPER(p.PRODUCT_NO) LIKE UPPER(:search)
                             OR UPPER(pt.PRODUCTTYPE_NAME) LIKE UPPER(:search)";
        $search_params[':search'] = '%' . $search . '%';
    }
    
    // Count total products
    $count_sql = "SELECT COUNT(*) as count 
                  FROM Products p 
                  LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID" . $search_condition;
    
    $stmt = oci_parse($connection, $count_sql);
    foreach ($search_params as $param => $value) {
        oci_bind_by_name($stmt, $param, $value);
    }
    oci_execute($stmt);
    $result = oci_fetch_assoc($stmt);
    $total_products = $result['COUNT'];
    $total_pages = ceil($total_products / $per_page);
    oci_free_statement($stmt);
    
    // Get products with pagination
    $sql = "SELECT * FROM (
                SELECT p.PRODUCT_NO, p.PRODUCTNAME, p.SELL_PRICE, p.COST_PRICE, 
                       p.QTY_ON_HAND, p.UNIT_MEASURE, p.REORDER_LEVEL,
                       pt.PRODUCTTYPE_NAME, p.PROFIT_PERCENT,
                       ROW_NUMBER() OVER (ORDER BY p.PRODUCT_NO) as rn
                FROM Products p 
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID" . 
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
    
    $products = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $products[] = $row;
    }
    oci_free_statement($stmt);
    
} catch (Exception $e) {
    $error_message = "Error loading products: " . $e->getMessage();
    $products = [];
    $total_products = 0;
    $total_pages = 0;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-box me-2"></i>Products Management
                    </h4>
                    <a href="add_product.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i>Add Product
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="products.php" class="d-flex">
                                <input type="text" class="form-control me-2" name="search" 
                                       placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="products.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products
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

                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Product No</th>
                                    <th>Product Name</th>
                                    <th>Type</th>
                                    <th>Sell Price</th>
                                    <th>Cost Price</th>
                                    <th>Profit %</th>
                                    <th>Stock</th>
                                    <th>Unit</th>
                                    <th>Reorder Level</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <div class="text-muted">
                                                <?php echo !empty($search) ? 'No products found matching your search.' : 'No products available.'; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['PRODUCT_NO'] ?? ''); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['PRODUCTNAME'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($product['PRODUCTTYPE_NAME'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?php echo Utils::formatCurrency($product['SELL_PRICE'] ?? 0); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo Utils::formatCurrency($product['COST_PRICE'] ?? 0); ?>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success">
                                                    <?php echo number_format($product['PROFIT_PERCENT'] ?? 0, 1); ?>%
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                $qty = $product['QTY_ON_HAND'] ?? 0;
                                                $reorder = $product['REORDER_LEVEL'] ?? 0;
                                                $badge_class = $qty <= $reorder ? 'bg-danger' : ($qty < ($reorder * 2) ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo number_format($qty); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['UNIT_MEASURE'] ?? ''); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">
                                                    <?php echo number_format($product['REORDER_LEVEL'] ?? 0); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="edit_product.php?id=<?php echo $product['PRODUCT_NO']; ?>" 
                                                       class="btn btn-outline-primary" 
                                                       data-bs-toggle="tooltip" title="Edit Product">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-info" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            title="Delete Product">
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
                        <nav aria-label="Products pagination">
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