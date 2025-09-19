<?php
// invoices.php - Invoice Management with CRUD Operations
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
$editInvoice = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    // Invoice editing is handled via AJAX in the JavaScript section
}

// Get all invoices for display
$invoices = getAllInvoices();
$clients = getAllClients();
$employees = getAllEmployees();
$products = getAllProducts();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice me-2"></i>Invoice Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#invoiceModal" onclick="resetForm()">
                        <i class="fas fa-plus me-1"></i>Add Invoice
                    </button>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($invoices)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Invoices Found</h5>
                            <p class="text-muted">Start by creating your first invoice.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceModal" onclick="resetForm()">
                                <i class="fas fa-plus me-1"></i>
                                Add Invoice
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="invoicesTable" class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Employee</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($invoice['INVOICENO']); ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($invoice['INVOICE_DATE'])); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['CLIENT_NAME'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['EMPLOYEE_NAME'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($invoice['INVOICE_STATUS']); ?>">
                                                    <?php echo htmlspecialchars($invoice['INVOICE_STATUS'] ?? 'Pending'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong>$<?php echo number_format($invoice['TOTAL_AMOUNT'] ?? 0, 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $invoice['ITEM_COUNT'] ?? 0; ?> items
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                        onclick="viewInvoiceDetails(<?php echo $invoice['INVOICENO']; ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#viewInvoiceModal">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editInvoice(<?php echo $invoice['INVOICENO']; ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#invoiceModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDelete(<?php echo $invoice['INVOICENO']; ?>, 'Invoice #<?php echo $invoice['INVOICENO']; ?>')">
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

<!-- Invoice Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalLabel">Add Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="invoiceForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="invoice_id" id="invoice_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="client_no" class="form-label">Client *</label>
                                <select class="form-select" name="client_no" id="client_no" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?php echo $client['CLIENT_NO']; ?>">
                                            <?php echo htmlspecialchars($client['CLIENTNAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Employee *</label>
                                <select class="form-select" name="employee_id" id="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['EMPLOYEEID']; ?>">
                                            <?php echo htmlspecialchars($employee['EMPLOYEENAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="invoice_date" class="form-label">Invoice Date *</label>
                                <input type="date" class="form-control" name="invoice_date" id="invoice_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="invoice_status" class="form-label">Status</label>
                                <select class="form-select" name="invoice_status" id="invoice_status">
                                    <option value="Pending">Pending</option>
                                    <option value="Confirmed">Confirmed</option>
                                    <option value="Shipped">Shipped</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="invoice_memo" class="form-label">Memo</label>
                        <textarea class="form-control" name="invoice_memo" id="invoice_memo" rows="2" 
                                  placeholder="Additional notes or comments"></textarea>
                    </div>
                    
                    <hr>
                    <h5>Invoice Items</h5>
                    
                    <div id="invoice-items">
                        <div class="invoice-item mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Product *</label>
                                    <select class="form-select product-select" name="products[]" required onchange="updatePrice(this)">
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['PRODUCT_NO']; ?>" 
                                                    data-price="<?php echo $product['SELL_PRICE']; ?>">
                                                <?php echo htmlspecialchars($product['PRODUCTNAME']); ?> - 
                                                $<?php echo number_format($product['SELL_PRICE'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity *</label>
                                    <input type="number" class="form-control quantity-input" name="quantities[]" 
                                           min="1" value="1" required onchange="calculateLineTotal(this)">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Price *</label>
                                    <input type="number" class="form-control price-input" name="prices[]" 
                                           step="0.01" min="0" required onchange="calculateLineTotal(this)">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Total</label>
                                    <input type="text" class="form-control line-total" readonly>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" 
                                            onclick="removeInvoiceItem(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary" onclick="addInvoiceItem()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h6>Invoice Total: <span id="invoice-total" class="text-success">$0.00</span></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Invoice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Invoice Modal -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-labelledby="viewInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewInvoiceModalLabel">Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="invoice-details-content">
                <!-- Invoice details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Store products data for JavaScript use
const productsData = <?php echo json_encode($products); ?>;
let itemCounter = 1;

function createProductOptions() {
    let options = '<option value="">Select Product</option>';
    productsData.forEach(product => {
        options += `<option value="${product.PRODUCT_NO}" data-price="${product.SELL_PRICE}">
                        ${product.PRODUCTNAME} - $${parseFloat(product.SELL_PRICE).toFixed(2)}
                    </option>`;
    });
    return options;
}

function resetForm() {
    document.getElementById('invoiceForm').reset();
    document.querySelector('#invoiceModal .modal-title').textContent = 'Add Invoice';
    document.querySelector('input[name="action"]').value = 'add';
    document.getElementById('invoice_date').value = new Date().toISOString().split('T')[0];
    
    // Reset to single item
    const itemsContainer = document.getElementById('invoice-items');
    itemsContainer.innerHTML = `
        <div class="invoice-item mb-3">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Product *</label>
                    <select class="form-select product-select" name="products[]" required onchange="updatePrice(this)">
                        ${createProductOptions()}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-control quantity-input" name="quantities[]" 
                           min="1" value="1" required onchange="calculateLineTotal(this)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price *</label>
                    <input type="number" class="form-control price-input" name="prices[]" 
                           step="0.01" min="0" required onchange="calculateLineTotal(this)">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control line-total" readonly>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" 
                            onclick="removeInvoiceItem(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    calculateInvoiceTotal();
}

function addInvoiceItem() {
    const itemsContainer = document.getElementById('invoice-items');
    const newItem = document.createElement('div');
    newItem.className = 'invoice-item mb-3';
    newItem.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Product *</label>
                <select class="form-select product-select" name="products[]" required onchange="updatePrice(this)">
                    ${createProductOptions()}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantity *</label>
                <input type="number" class="form-control quantity-input" name="quantities[]" 
                       min="1" value="1" required onchange="calculateLineTotal(this)">
            </div>
            <div class="col-md-3">
                <label class="form-label">Price *</label>
                <input type="number" class="form-control price-input" name="prices[]" 
                       step="0.01" min="0" required onchange="calculateLineTotal(this)">
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text" class="form-control line-total" readonly>
                <button type="button" class="btn btn-sm btn-outline-danger mt-1" 
                        onclick="removeInvoiceItem(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    itemsContainer.appendChild(newItem);
}

function removeInvoiceItem(button) {
    const itemsContainer = document.getElementById('invoice-items');
    if (itemsContainer.children.length > 1) {
        button.closest('.invoice-item').remove();
        calculateInvoiceTotal();
    }
}

function updatePrice(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    const priceInput = selectElement.closest('.row').querySelector('.price-input');
    if (price) {
        priceInput.value = parseFloat(price).toFixed(2);
        calculateLineTotal(priceInput);
    }
}

function calculateLineTotal(element) {
    const row = element.closest('.row');
    const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const total = quantity * price;
    
    row.querySelector('.line-total').value = '$' + total.toFixed(2);
    calculateInvoiceTotal();
}

function calculateInvoiceTotal() {
    let total = 0;
    document.querySelectorAll('.line-total').forEach(function(element) {
        const value = element.value.replace('$', '');
        total += parseFloat(value) || 0;
    });
    
    document.getElementById('invoice-total').textContent = '$' + total.toFixed(2);
}

function editInvoice(invoiceId) {
    // Load invoice data via AJAX
    fetch(`invoices.php?action=get_invoice&id=${invoiceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateInvoiceForm(data.invoice, data.details);
            } else {
                alert('Error loading invoice data');
            }
        });
}

function createProductOptionsWithSelected(selectedProductNo) {
    let options = '<option value="">Select Product</option>';
    productsData.forEach(product => {
        const selected = product.PRODUCT_NO === selectedProductNo ? 'selected' : '';
        options += `<option value="${product.PRODUCT_NO}" data-price="${product.SELL_PRICE}" ${selected}>
                        ${product.PRODUCTNAME} - $${parseFloat(product.SELL_PRICE).toFixed(2)}
                    </option>`;
    });
    return options;
}

function populateInvoiceForm(invoice, details) {
    document.querySelector('#invoiceModal .modal-title').textContent = 'Edit Invoice';
    document.querySelector('input[name="action"]').value = 'edit';
    document.getElementById('invoice_id').value = invoice.INVOICENO;
    document.getElementById('client_no').value = invoice.CLIENT_NO;
    document.getElementById('employee_id').value = invoice.EMPLOYEEID;
    document.getElementById('invoice_date').value = invoice.INVOICE_DATE.split(' ')[0];
    document.getElementById('invoice_status').value = invoice.INVOICE_STATUS;
    document.getElementById('invoice_memo').value = invoice.INVOICEMEMO || '';
    
    // Clear and populate items
    const itemsContainer = document.getElementById('invoice-items');
    itemsContainer.innerHTML = '';
    
    details.forEach(detail => {
        const newItem = document.createElement('div');
        newItem.className = 'invoice-item mb-3';
        newItem.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Product *</label>
                    <select class="form-select product-select" name="products[]" required onchange="updatePrice(this)">
                        ${createProductOptionsWithSelected(detail.PRODUCT_NO)}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-control quantity-input" name="quantities[]" 
                           min="1" value="${detail.QTY}" required onchange="calculateLineTotal(this)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price *</label>
                    <input type="number" class="form-control price-input" name="prices[]" 
                           step="0.01" min="0" value="${detail.PRICE}" required onchange="calculateLineTotal(this)">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control line-total" readonly value="$${(detail.QTY * detail.PRICE).toFixed(2)}">
                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" 
                            onclick="removeInvoiceItem(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        itemsContainer.appendChild(newItem);
    });
    
    calculateInvoiceTotal();
}

function viewInvoiceDetails(invoiceId) {
    fetch(`invoices.php?action=get_invoice_details&id=${invoiceId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('invoice-details-content').innerHTML = html;
        });
}

function confirmDelete(id, name) {
    if (confirm(`Are you sure you want to delete ${name}? This will also delete all associated invoice details.`)) {
        window.location.href = `invoices.php?action=delete&id=${id}`;
    }
}

</script>

<?php
// PHP Functions

function getAllInvoices() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT i.INVOICENO, i.INVOICE_DATE, i.CLIENT_NO, i.EMPLOYEEID, i.INVOICE_STATUS, i.INVOICEMEMO,
                       c.CLIENTNAME as CLIENT_NAME, e.EMPLOYEENAME as EMPLOYEE_NAME,
                       NVL(totals.TOTAL_AMOUNT, 0) as TOTAL_AMOUNT,
                       NVL(totals.ITEM_COUNT, 0) as ITEM_COUNT
                FROM INVOICES i
                LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                LEFT JOIN (
                    SELECT INVOICENO, 
                           SUM(QTY * PRICE) as TOTAL_AMOUNT,
                           COUNT(*) as ITEM_COUNT
                    FROM INVOICE_DETAILS 
                    GROUP BY INVOICENO
                ) totals ON i.INVOICENO = totals.INVOICENO
                ORDER BY i.INVOICENO DESC";
        
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        setFlashMessage('Error loading invoices: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function getAllClients() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT CLIENT_NO, CLIENTNAME FROM Clients ORDER BY CLIENTNAME";
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        return [];
    }
}

function getAllEmployees() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT EMPLOYEEID, EMPLOYEENAME FROM Employees ORDER BY EMPLOYEENAME";
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        return [];
    }
}

function getAllProducts() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT PRODUCT_NO, PRODUCTNAME, SELL_PRICE FROM Products ORDER BY PRODUCTNAME";
        $stmt = $db->query($sql);
        return $db->fetchAll($stmt);
    } catch (Exception $e) {
        return [];
    }
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'bg-warning';
        case 'confirmed': return 'bg-info';
        case 'shipped': return 'bg-primary';
        case 'delivered': return 'bg-success';
        case 'cancelled': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function handleFormSubmission() {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            addInvoice($_POST);
            break;
        case 'edit':
            updateInvoice($_POST);
            break;
    }
}

function addInvoice($data) {
    try {
        $db = Database::getInstance();
        
        // Validate required fields
        if (empty($data['client_no']) || empty($data['employee_id']) || empty($data['products'][0])) {
            setFlashMessage('Please fill in all required fields and add at least one product.', 'danger');
            return;
        }
        
        // Start transaction
        $db->getConnection();
        
        // Insert invoice
        $sql = "INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) 
                VALUES (TO_DATE(:invoice_date, 'YYYY-MM-DD'), :client_no, :employee_id, :invoice_status, :invoice_memo) 
                RETURNING INVOICENO INTO :invoice_no";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        $invoice_date = $data['invoice_date'];
        $client_no = $data['client_no'];
        $employee_id = $data['employee_id'];
        $invoice_status = $data['invoice_status'] ?? 'Pending';
        $invoice_memo = $data['invoice_memo'] ?? null;
        $invoice_no = 0;
        
        oci_bind_by_name($stmt, ':invoice_date', $invoice_date);
        oci_bind_by_name($stmt, ':client_no', $client_no);
        oci_bind_by_name($stmt, ':employee_id', $employee_id);
        oci_bind_by_name($stmt, ':invoice_status', $invoice_status);
        oci_bind_by_name($stmt, ':invoice_memo', $invoice_memo);
        oci_bind_by_name($stmt, ':invoice_no', $invoice_no, -1, SQLT_INT);
        
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        
        oci_free_statement($stmt);
        
        // Insert invoice details
        $products = $data['products'];
        $quantities = $data['quantities'];
        $prices = $data['prices'];
        
        for ($i = 0; $i < count($products); $i++) {
            if (!empty($products[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
                $sql = "INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) 
                        VALUES (:invoice_no, :product_no, :qty, :price)";
                
                $stmt = oci_parse($db->getConnection(), $sql);
                
                $product_no = $products[$i];
                $qty = $quantities[$i];
                $price = $prices[$i];
                
                oci_bind_by_name($stmt, ':invoice_no', $invoice_no);
                oci_bind_by_name($stmt, ':product_no', $product_no);
                oci_bind_by_name($stmt, ':qty', $qty);
                oci_bind_by_name($stmt, ':price', $price);
                
                $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                if (!$result) {
                    $error = oci_error($stmt);
                    throw new Exception($error['message']);
                }
                
                oci_free_statement($stmt);
            }
        }
        
        oci_commit($db->getConnection());
        setFlashMessage('Invoice created successfully!', 'success');
        header('Location: invoices.php');
        exit;
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        oci_rollback($db->getConnection());
        setFlashMessage('Error creating invoice: ' . $e->getMessage(), 'danger');
    }
}

function updateInvoice($data) {
    try {
        $db = Database::getInstance();
        
        // Validate required fields
        if (empty($data['invoice_id']) || empty($data['client_no']) || empty($data['employee_id']) || empty($data['products'][0])) {
            setFlashMessage('Please fill in all required fields and add at least one product.', 'danger');
            return;
        }
        
        $invoice_id = $data['invoice_id'];
        
        // Update invoice
        $sql = "UPDATE INVOICES SET INVOICE_DATE = TO_DATE(:invoice_date, 'YYYY-MM-DD'), 
                CLIENT_NO = :client_no, EMPLOYEEID = :employee_id, 
                INVOICE_STATUS = :invoice_status, INVOICEMEMO = :invoice_memo 
                WHERE INVOICENO = :invoice_id";
        
        $stmt = oci_parse($db->getConnection(), $sql);
        
        $invoice_date = $data['invoice_date'];
        $client_no = $data['client_no'];
        $employee_id = $data['employee_id'];
        $invoice_status = $data['invoice_status'] ?? 'Pending';
        $invoice_memo = $data['invoice_memo'] ?? null;
        
        oci_bind_by_name($stmt, ':invoice_date', $invoice_date);
        oci_bind_by_name($stmt, ':client_no', $client_no);
        oci_bind_by_name($stmt, ':employee_id', $employee_id);
        oci_bind_by_name($stmt, ':invoice_status', $invoice_status);
        oci_bind_by_name($stmt, ':invoice_memo', $invoice_memo);
        oci_bind_by_name($stmt, ':invoice_id', $invoice_id);
        
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        
        oci_free_statement($stmt);
        
        // Delete existing details
        $sql = "DELETE FROM INVOICE_DETAILS WHERE INVOICENO = :invoice_id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':invoice_id', $invoice_id);
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        oci_free_statement($stmt);
        
        // Insert new details
        $products = $data['products'];
        $quantities = $data['quantities'];
        $prices = $data['prices'];
        
        for ($i = 0; $i < count($products); $i++) {
            if (!empty($products[$i]) && !empty($quantities[$i]) && !empty($prices[$i])) {
                $sql = "INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) 
                        VALUES (:invoice_id, :product_no, :qty, :price)";
                
                $stmt = oci_parse($db->getConnection(), $sql);
                
                $product_no = $products[$i];
                $qty = $quantities[$i];
                $price = $prices[$i];
                
                oci_bind_by_name($stmt, ':invoice_id', $invoice_id);
                oci_bind_by_name($stmt, ':product_no', $product_no);
                oci_bind_by_name($stmt, ':qty', $qty);
                oci_bind_by_name($stmt, ':price', $price);
                
                $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
                if (!$result) {
                    $error = oci_error($stmt);
                    throw new Exception($error['message']);
                }
                
                oci_free_statement($stmt);
            }
        }
        
        oci_commit($db->getConnection());
        setFlashMessage('Invoice updated successfully!', 'success');
        header('Location: invoices.php');
        exit;
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        oci_rollback($db->getConnection());
        setFlashMessage('Error updating invoice: ' . $e->getMessage(), 'danger');
    }
}

function handleDelete($id) {
    try {
        $db = Database::getInstance();
        
        // Delete invoice details first (foreign key constraint)
        $sql = "DELETE FROM INVOICE_DETAILS WHERE INVOICENO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        oci_free_statement($stmt);
        
        // Delete invoice
        $sql = "DELETE FROM INVOICES WHERE INVOICENO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
        $result = oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        
        if (!$result) {
            $error = oci_error($stmt);
            throw new Exception($error['message']);
        }
        
        oci_commit($db->getConnection());
        oci_free_statement($stmt);
        
        setFlashMessage('Invoice deleted successfully!', 'success');
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        oci_rollback($db->getConnection());
        setFlashMessage('Error deleting invoice: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: invoices.php');
    exit;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_invoice':
            getInvoiceAjax($_GET['id']);
            break;
        case 'get_invoice_details':
            getInvoiceDetailsAjax($_GET['id']);
            break;
    }
}

function getInvoiceAjax($id) {
    try {
        $db = Database::getInstance();
        
        // Get invoice
        $sql = "SELECT * FROM INVOICES WHERE INVOICENO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
        oci_execute($stmt);
        $invoice = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        // Get details
        $sql = "SELECT * FROM INVOICE_DETAILS WHERE INVOICENO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
        oci_execute($stmt);
        
        $details = [];
        while (($row = oci_fetch_assoc($stmt)) !== false) {
            $details[] = $row;
        }
        oci_free_statement($stmt);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'invoice' => $invoice, 'details' => $details]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

function getInvoiceDetailsAjax($id) {
    try {
        $db = Database::getInstance();
        
        // Get invoice with client and employee info
        $sql = "SELECT i.*, c.CLIENTNAME, e.EMPLOYEENAME 
                FROM INVOICES i
                LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                WHERE i.INVOICENO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
        oci_execute($stmt);
        $invoice = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        // Get details with product info
        $sql = "SELECT id.*, p.PRODUCTNAME 
                FROM INVOICE_DETAILS id
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE id.INVOICENO = :id";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':id', $id);
        oci_execute($stmt);
        
        $details = [];
        $total = 0;
        while (($row = oci_fetch_assoc($stmt)) !== false) {
            $details[] = $row;
            $total += $row['QTY'] * $row['PRICE'];
        }
        oci_free_statement($stmt);
        
        // Generate HTML
        ob_start();
        ?>
        <div class="invoice-header mb-4">
            <div class="row">
                <div class="col-md-6">
                    <h6><strong>Invoice #<?php echo $invoice['INVOICENO']; ?></strong></h6>
                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['INVOICE_DATE'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?php echo getStatusBadgeClass($invoice['INVOICE_STATUS']); ?>">
                            <?php echo $invoice['INVOICE_STATUS']; ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Client:</strong> <?php echo htmlspecialchars($invoice['CLIENTNAME']); ?></p>
                    <p><strong>Employee:</strong> <?php echo htmlspecialchars($invoice['EMPLOYEENAME']); ?></p>
                    <?php if ($invoice['INVOICEMEMO']): ?>
                        <p><strong>Memo:</strong> <?php echo htmlspecialchars($invoice['INVOICEMEMO']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detail['PRODUCTNAME']); ?></td>
                            <td><?php echo $detail['QTY']; ?></td>
                            <td>$<?php echo number_format($detail['PRICE'], 2); ?></td>
                            <td>$<?php echo number_format($detail['QTY'] * $detail['PRICE'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3">Total</th>
                        <th>$<?php echo number_format($total, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
        echo ob_get_clean();
        exit;
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error loading invoice details: ' . $e->getMessage() . '</div>';
        exit;
    }
}

require_once 'includes/footer.php';
?>
