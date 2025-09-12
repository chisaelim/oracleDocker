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
        $search_condition = " WHERE UPPER(c.CLIENTNAME) LIKE UPPER(:search) 
                             OR UPPER(e.EMPLOYEENAME) LIKE UPPER(:search)
                             OR UPPER(i.INVOICE_STATUS) LIKE UPPER(:search)
                             OR TO_CHAR(i.INVOICENO) LIKE :search_num";
        $search_params[':search'] = '%' . $search . '%';
        $search_params[':search_num'] = '%' . $search . '%';
    }
    
    // Count total invoices
    $count_sql = "SELECT COUNT(*) as count 
                  FROM INVOICES i 
                  LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                  LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID" . $search_condition;
    
    $stmt = oci_parse($connection, $count_sql);
    foreach ($search_params as $param => $value) {
        oci_bind_by_name($stmt, $param, $value);
    }
    oci_execute($stmt);
    $result = oci_fetch_assoc($stmt);
    $total_invoices = $result['COUNT'];
    $total_pages = ceil($total_invoices / $per_page);
    oci_free_statement($stmt);
    
    // Get invoices with pagination
    $sql = "SELECT * FROM (
                SELECT i.INVOICENO, i.INVOICE_DATE, i.INVOICE_STATUS, i.INVOICEMEMO,
                       c.CLIENTNAME, c.CLIENT_NO,
                       e.EMPLOYEENAME,
                       (SELECT SUM(id.QTY * id.PRICE) 
                        FROM INVOICE_DETAILS id 
                        WHERE id.INVOICENO = i.INVOICENO) as TOTAL_AMOUNT,
                       (SELECT COUNT(*) 
                        FROM INVOICE_DETAILS id 
                        WHERE id.INVOICENO = i.INVOICENO) as ITEM_COUNT,
                       ROW_NUMBER() OVER (ORDER BY i.INVOICENO DESC) as rn
                FROM INVOICES i 
                LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID" . 
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
    
    $invoices = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $invoices[] = $row;
    }
    oci_free_statement($stmt);
    
} catch (Exception $e) {
    $error_message = "Error loading invoices: " . $e->getMessage();
    $invoices = [];
    $total_invoices = 0;
    $total_pages = 0;
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice me-2"></i>Invoices Management
                    </h4>
                    <a href="add_invoice.php" class="btn btn-light">
                        <i class="fas fa-plus me-1"></i>Create Invoice
                    </a>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="GET" action="invoices.php" class="d-flex">
                                <input type="text" class="form-control me-2" name="search" 
                                       placeholder="Search invoices..." value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="invoices.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Showing <?php echo count($invoices); ?> of <?php echo $total_invoices; ?> invoices
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

                    <!-- Invoices Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th>Employee</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                                            <div class="text-muted">
                                                <?php echo !empty($search) ? 'No invoices found matching your search.' : 'No invoices available.'; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-primary">#<?php echo htmlspecialchars($invoice['INVOICENO'] ?? ''); ?></strong>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($invoice['INVOICE_DATE'])) {
                                                    $date = new DateTime($invoice['INVOICE_DATE']);
                                                    echo $date->format('M d, Y');
                                                    echo '<br><small class="text-muted">' . $date->format('H:i') . '</small>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($invoice['CLIENTNAME'] ?? 'N/A'); ?></strong>
                                                    <?php if (!empty($invoice['CLIENT_NO'])): ?>
                                                        <br><small class="text-muted">ID: <?php echo $invoice['CLIENT_NO']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($invoice['EMPLOYEENAME'] ?? 'N/A'); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">
                                                    <?php echo intval($invoice['ITEM_COUNT'] ?? 0); ?> items
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <strong class="text-success">
                                                    <?php echo Utils::formatCurrency($invoice['TOTAL_AMOUNT'] ?? 0); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php 
                                                $status = $invoice['INVOICE_STATUS'] ?? '';
                                                $badge_class = 'bg-secondary';
                                                switch (strtolower($status)) {
                                                    case 'paid':
                                                        $badge_class = 'bg-success';
                                                        break;
                                                    case 'pending':
                                                        $badge_class = 'bg-warning';
                                                        break;
                                                    case 'cancelled':
                                                    case 'canceled':
                                                        $badge_class = 'bg-danger';
                                                        break;
                                                    case 'draft':
                                                        $badge_class = 'bg-info';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo htmlspecialchars($status ?: 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-info" 
                                                            title="View Invoice"
                                                            onclick="viewInvoice(<?php echo $invoice['INVOICENO']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            title="Edit Invoice">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            title="Print Invoice">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            title="Delete Invoice">
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
                        <nav aria-label="Invoices pagination">
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

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalLabel">Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="invoiceDetails">
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
function viewInvoice(invoiceNo) {
    const modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
    const modalBody = document.getElementById('invoiceDetails');
    
    // Show loading spinner
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Simulate loading invoice details (you would make an AJAX call here)
    setTimeout(() => {
        modalBody.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Invoice details functionality would be implemented here with invoice #${invoiceNo}
            </div>
        `;
    }, 500);
}
</script>

<?php require_once './includes/footer.php'; ?>