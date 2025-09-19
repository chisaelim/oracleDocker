<?php
// invoices.php - Invoice Management with CRUD Operations
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/utils.php';

// AJAX function definitions (must be before any HTML output)
function getInvoiceAjax($id) {
    try {
        $db = Database::getInstance();
        
        // Get invoice
        $sql = "SELECT * FROM INVOICES WHERE INVOICENO = :id";
        $params = [':id' => $id];
        $stmt = $db->query($sql, $params);
        $invoice = $db->fetchOne($stmt);
        
        // Get details
        $sql = "SELECT * FROM INVOICE_DETAILS WHERE INVOICENO = :id";
        $params = [':id' => $id];
        $stmt = $db->query($sql, $params);
        $details = $db->fetchAll($stmt);
        
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
        $params = [':id' => $id];
        $stmt = $db->query($sql, $params);
        $invoice = $db->fetchOne($stmt);
        
        // Get details with product info
        $sql = "SELECT id.*, p.PRODUCTNAME 
                FROM INVOICE_DETAILS id
                LEFT JOIN Products p ON id.PRODUCT_NO = p.PRODUCT_NO
                WHERE id.INVOICENO = :id";
        $params = [':id' => $id];
        $stmt = $db->query($sql, $params);
        $details = $db->fetchAll($stmt);
        
        $total = 0;
        foreach ($details as $row) {
            $total += $row['QTY'] * $row['PRICE'];
        }
        
        // Generate clean HTML without navbar for viewing
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Invoice #<?php echo $invoice['INVOICENO']; ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .invoice-header { border-bottom: 2px solid #dee2e6; padding-bottom: 20px; margin-bottom: 30px; }
                .company-header { text-align: center; margin-bottom: 30px; }
                @media print {
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="company-header">
                    <h2 class="text-primary"><i class="fas fa-building me-2"></i>Oracle Project Company</h2>
                    <p class="text-muted">Invoice Management System</p>
                </div>
                
                <div class="invoice-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><strong>Invoice #<?php echo $invoice['INVOICENO']; ?></strong></h4>
                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($invoice['INVOICE_DATE'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge <?php echo getStatusBadgeClass($invoice['INVOICE_STATUS']); ?>">
                                    <?php echo $invoice['INVOICE_STATUS']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
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
                        <thead class="table-primary">
                            <tr>
                                <th>Product</th>
                                <th width="15%">Quantity</th>
                                <th width="15%">Unit Price</th>
                                <th width="15%">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($details as $detail): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detail['PRODUCTNAME']); ?></td>
                                    <td class="text-center"><?php echo $detail['QTY']; ?></td>
                                    <td class="text-end">$<?php echo number_format($detail['PRICE'], 2); ?></td>
                                    <td class="text-end"><strong>$<?php echo number_format($detail['QTY'] * $detail['PRICE'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th colspan="3" class="text-end">Grand Total:</th>
                                <th class="text-end">$<?php echo number_format($total, 2); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="mt-4 no-print">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print Invoice
                    </button>
                    <button class="btn btn-secondary ms-2" onclick="window.close()">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error loading invoice details: ' . $e->getMessage() . '</div>';
        exit;
    }
}

function getClientDiscountAjax($clientNo) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT DISCOUNT FROM CLIENTS WHERE CLIENT_NO = :client_no";
        $params = [':client_no' => $clientNo];
        $stmt = $db->query($sql, $params);
        $result = $db->fetchOne($stmt);
        
        $discount = $result['DISCOUNT'] ?? 0;
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'discount' => $discount]);
        exit;
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Handle AJAX requests first (before any HTML output)
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_invoice':
            if (isset($_GET['id'])) {
                getInvoiceAjax($_GET['id']);
            }
            break;
        case 'get_invoice_details':
            if (isset($_GET['id'])) {
                getInvoiceDetailsAjax($_GET['id']);
            }
            break;
        case 'get_client_discount':
            if (isset($_GET['client_no'])) {
                getClientDiscountAjax($_GET['client_no']);
            }
            break;
        case 'delete':
            if (isset($_GET['id'])) {
                handleDelete($_GET['id']);
            }
            break;
    }
}

// Form processing functions (defined before use)
function processAddInvoice($data) {
    try {
        $db = Database::getInstance();
        
        // Validate required fields
        if (empty($data['client_no']) || empty($data['employee_id'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            header('Location: invoices.php');
            exit;
        }
        
        // Fetch client discount percentage
        $sql = "SELECT DISCOUNT FROM CLIENTS WHERE CLIENT_NO = :client_no";
        $params = [':client_no' => $data['client_no']];
        $stmt = $db->query($sql, $params);
        $clientResult = $db->fetchOne($stmt);
        $clientDiscount = $clientResult['DISCOUNT'] ?? 0;
        
        // Validate invoice details - must have at least one valid item
        $validItems = 0;
        if (!empty($data['products']) && is_array($data['products'])) {
            for ($i = 0; $i < count($data['products']); $i++) {
                if (!empty($data['products'][$i]) && 
                    !empty($data['quantities'][$i]) && 
                    !empty($data['prices'][$i]) &&
                    $data['quantities'][$i] > 0 &&
                    $data['prices'][$i] >= 0) {
                    $validItems++;
                }
            }
        }
        
        if ($validItems === 0) {
            setFlashMessage('An invoice must have at least one valid product item with quantity and price.', 'danger');
            header('Location: invoices.php');
            exit;
        }
        
        // Insert invoice first to get the invoice number
        $sql = "INSERT INTO INVOICES (INVOICE_DATE, CLIENT_NO, EMPLOYEEID, INVOICE_STATUS, INVOICEMEMO) 
                VALUES (TO_DATE(:invoice_date, 'YYYY-MM-DD'), :client_no, :employee_id, :invoice_status, :invoice_memo)";
        
        $params = [
            ':invoice_date' => $data['invoice_date'],
            ':client_no' => $data['client_no'],
            ':employee_id' => $data['employee_id'],
            ':invoice_status' => $data['invoice_status'] ?? 'Pending',
            ':invoice_memo' => $data['invoice_memo'] ?? null
        ];
        
        $stmt = $db->query($sql, $params);
        
        // Get the last inserted invoice number
        $sql = "SELECT MAX(INVOICENO) AS LAST_INVOICE FROM INVOICES WHERE CLIENT_NO = :client_no AND EMPLOYEEID = :employee_id";
        $params = [
            ':client_no' => $data['client_no'],
            ':employee_id' => $data['employee_id']
        ];
        $stmt = $db->query($sql, $params);
        $result = $db->fetchOne($stmt);
        $invoice_no = $result['LAST_INVOICE'];
        
        if (!$invoice_no) {
            throw new Exception("Failed to get invoice number");
        }
        
        // Insert invoice details - only insert valid items
        $products = $data['products'];
        $quantities = $data['quantities'];
        $prices = $data['prices'];
        $insertedItems = 0;
        $subtotal = 0;
        
        for ($i = 0; $i < count($products); $i++) {
            if (!empty($products[$i]) && 
                !empty($quantities[$i]) && 
                !empty($prices[$i]) &&
                $quantities[$i] > 0 &&
                $prices[$i] >= 0) {
                
                // Calculate item total and add to subtotal
                $itemTotal = $quantities[$i] * $prices[$i];
                $subtotal += $itemTotal;
                
                $sql = "INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) 
                        VALUES (:invoice_no, :product_no, :qty, :price)";
                
                $params = [
                    ':invoice_no' => $invoice_no,
                    ':product_no' => $products[$i],
                    ':qty' => $quantities[$i],
                    ':price' => $prices[$i]
                ];
                
                $stmt = $db->query($sql, $params);
                $insertedItems++;
            }
        }
        
        // Calculate discount amount and final total
        $discountAmount = ($subtotal * $clientDiscount) / 100;
        $finalTotal = $subtotal - $discountAmount;
        
        // Final check to ensure we actually inserted items
        if ($insertedItems === 0) {
            throw new Exception("No valid invoice items were inserted. Invoice cannot be created without items.");
        }
        
        $db->commit();
        $discountInfo = $clientDiscount > 0 ? sprintf(" (Subtotal: $%.2f, Discount: %.1f%%, Final Total: $%.2f)", $subtotal, $clientDiscount, $finalTotal) : "";
        setFlashMessage('Invoice created successfully!' . $discountInfo, 'success');
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        $db->rollback();
        setFlashMessage('Error creating invoice: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: invoices.php');
    exit;
}

function processUpdateInvoice($data) {
    try {
        $db = Database::getInstance();
        
        // Validate required fields
        if (empty($data['invoice_id']) || empty($data['client_no']) || empty($data['employee_id'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            header('Location: invoices.php');
            exit;
        }
        
        // Fetch client discount percentage
        $sql = "SELECT DISCOUNT FROM CLIENTS WHERE CLIENT_NO = :client_no";
        $params = [':client_no' => $data['client_no']];
        $stmt = $db->query($sql, $params);
        $clientResult = $db->fetchOne($stmt);
        $clientDiscount = $clientResult['DISCOUNT'] ?? 0;
        
        // Validate invoice details - must have at least one valid item
        $validItems = 0;
        if (!empty($data['products']) && is_array($data['products'])) {
            for ($i = 0; $i < count($data['products']); $i++) {
                if (!empty($data['products'][$i]) && 
                    !empty($data['quantities'][$i]) && 
                    !empty($data['prices'][$i]) &&
                    $data['quantities'][$i] > 0 &&
                    $data['prices'][$i] >= 0) {
                    $validItems++;
                }
            }
        }
        
        if ($validItems === 0) {
            setFlashMessage('An invoice must have at least one valid product item with quantity and price.', 'danger');
            header('Location: invoices.php');
            exit;
        }
        
        $invoice_id = $data['invoice_id'];
        
        // Update invoice
        $sql = "UPDATE INVOICES SET INVOICE_DATE = TO_DATE(:invoice_date, 'YYYY-MM-DD'), 
                CLIENT_NO = :client_no, EMPLOYEEID = :employee_id, 
                INVOICE_STATUS = :invoice_status, INVOICEMEMO = :invoice_memo 
                WHERE INVOICENO = :invoice_id";
        
        $params = [
            ':invoice_date' => $data['invoice_date'],
            ':client_no' => $data['client_no'],
            ':employee_id' => $data['employee_id'],
            ':invoice_status' => $data['invoice_status'] ?? 'Pending',
            ':invoice_memo' => $data['invoice_memo'] ?? null,
            ':invoice_id' => $invoice_id
        ];
        
        $stmt = $db->query($sql, $params);
        
        // Delete existing details
        $sql = "DELETE FROM INVOICE_DETAILS WHERE INVOICENO = :invoice_id";
        $params = [':invoice_id' => $invoice_id];
        $stmt = $db->query($sql, $params);
        
        // Insert new details - only insert valid items
        $products = $data['products'];
        $quantities = $data['quantities'];
        $prices = $data['prices'];
        $insertedItems = 0;
        $subtotal = 0;
        
        for ($i = 0; $i < count($products); $i++) {
            if (!empty($products[$i]) && 
                !empty($quantities[$i]) && 
                !empty($prices[$i]) &&
                $quantities[$i] > 0 &&
                $prices[$i] >= 0) {
                
                // Calculate item total and add to subtotal
                $itemTotal = $quantities[$i] * $prices[$i];
                $subtotal += $itemTotal;
                
                $sql = "INSERT INTO INVOICE_DETAILS (INVOICENO, PRODUCT_NO, QTY, PRICE) 
                        VALUES (:invoice_id, :product_no, :qty, :price)";
                
                $params = [
                    ':invoice_id' => $invoice_id,
                    ':product_no' => $products[$i],
                    ':qty' => $quantities[$i],
                    ':price' => $prices[$i]
                ];
                
                $stmt = $db->query($sql, $params);
                $insertedItems++;
            }
        }
        
        // Calculate discount amount and final total
        $discountAmount = ($subtotal * $clientDiscount) / 100;
        $finalTotal = $subtotal - $discountAmount;
        
        // Final check to ensure we actually inserted items
        if ($insertedItems === 0) {
            throw new Exception("No valid invoice items were inserted. Invoice cannot exist without items.");
        }
        
        $db->commit();
        $discountInfo = $clientDiscount > 0 ? sprintf(" (Subtotal: $%.2f, Discount: %.1f%%, Final Total: $%.2f)", $subtotal, $clientDiscount, $finalTotal) : "";
        setFlashMessage('Invoice updated successfully!' . $discountInfo, 'success');
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        $db->rollback();
        setFlashMessage('Error updating invoice: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: invoices.php');
    exit;
}

// Handle form submissions BEFORE including header (to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We need to handle form submissions before any output
    // Process the form and redirect
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            processAddInvoice($_POST);
            break;
        case 'edit':
            processUpdateInvoice($_POST);
            break;
    }
}

// Now include header after AJAX and form handling
require_once 'includes/header.php';

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
                                        <th style="min-width: 100px;">Invoice No</th>
                                        <th style="min-width: 100px;">Date</th>
                                        <th style="min-width: 120px;">Client</th>
                                        <th style="min-width: 120px;">Employee</th>
                                        <th style="min-width: 90px;">Status</th>
                                        <th style="min-width: 100px;">Subtotal</th>
                                        <th style="min-width: 90px;">Discount %</th>
                                        <th style="min-width: 110px;">Discount Amount</th>
                                        <th style="min-width: 110px;">Final Total</th>
                                        <th style="min-width: 80px;">Items</th>
                                        <th style="min-width: 130px;">Actions</th>
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
                                                $<?php echo number_format($invoice['SUBTOTAL_AMOUNT'] ?? 0, 2); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $discount = $invoice['CLIENT_DISCOUNT'] ?? 0;
                                                if ($discount > 0): ?>
                                                    <span class="badge bg-warning text-dark"><?php echo number_format($discount, 1); ?>%</span>
                                                <?php else: ?>
                                                    <span class="text-muted">0%</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $discountAmount = $invoice['DISCOUNT_AMOUNT'] ?? 0;
                                                if ($discountAmount > 0): ?>
                                                    <span class="text-danger">-$<?php echo number_format($discountAmount, 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">$0.00</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-success">$<?php echo number_format($invoice['FINAL_TOTAL'] ?? 0, 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $invoice['ITEM_COUNT'] ?? 0; ?> items
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                        onclick="viewInvoiceDetails(<?php echo $invoice['INVOICENO']; ?>)">
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
                                    <div class="small text-muted mb-1">
                                        <div>Subtotal: <span id="invoice-subtotal">$0.00</span></div>
                                        <div id="discount-info" style="display: none;">
                                            Discount (<span id="discount-percent">0</span>%): 
                                            -<span id="discount-amount">$0.00</span>
                                        </div>
                                    </div>
                                    <h6>Total: <span id="invoice-total" class="text-success">$0.00</span></h6>
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

<!-- View Invoice Modal removed - now opens in new window -->

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
    
    // Reset discount information
    clientDiscount = 0;
    
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
    } else {
        alert('An invoice must have at least one item. Cannot remove the last item.');
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

let clientDiscount = 0; // Global variable to store client discount

function calculateInvoiceTotal() {
    let subtotal = 0;
    document.querySelectorAll('.line-total').forEach(function(element) {
        const value = element.value.replace('$', '');
        subtotal += parseFloat(value) || 0;
    });
    
    // Calculate discount
    const discountAmount = (subtotal * clientDiscount) / 100;
    const finalTotal = subtotal - discountAmount;
    
    // Update display
    document.getElementById('invoice-subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('invoice-total').textContent = '$' + finalTotal.toFixed(2);
    
    // Show/hide discount info
    const discountInfo = document.getElementById('discount-info');
    if (clientDiscount > 0) {
        document.getElementById('discount-percent').textContent = clientDiscount.toFixed(1);
        document.getElementById('discount-amount').textContent = '$' + discountAmount.toFixed(2);
        discountInfo.style.display = 'block';
    } else {
        discountInfo.style.display = 'none';
    }
}

function fetchClientDiscount(clientNo) {
    if (!clientNo) {
        clientDiscount = 0;
        calculateInvoiceTotal();
        return;
    }
    
    fetch(`invoices.php?action=get_client_discount&client_no=${clientNo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                clientDiscount = parseFloat(data.discount) || 0;
                calculateInvoiceTotal();
            } else {
                clientDiscount = 0;
                calculateInvoiceTotal();
            }
        })
        .catch(error => {
            console.error('Error fetching client discount:', error);
            clientDiscount = 0;
            calculateInvoiceTotal();
        });
}

// Add event listener for client selection
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.getElementById('client_no');
    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            fetchClientDiscount(this.value);
        });
    }
});

function editInvoice(invoiceId) {
    // Load invoice data via AJAX
    fetch(`invoices.php?action=get_invoice&id=${invoiceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateInvoiceForm(data.invoice, data.details);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: 'Error loading invoice data',
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: 'Failed to load invoice data',
                icon: 'error'
            });
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
    // Set modal title and form action
    document.querySelector('#invoiceModal .modal-title').textContent = 'Edit Invoice';
    document.querySelector('input[name="action"]').value = 'edit';
    document.getElementById('invoice_id').value = invoice.INVOICENO;
    
    // Populate basic invoice fields
    document.getElementById('client_no').value = invoice.CLIENT_NO;
    document.getElementById('employee_id').value = invoice.EMPLOYEEID;
    document.getElementById('invoice_status').value = invoice.INVOICE_STATUS;
    document.getElementById('invoice_memo').value = invoice.INVOICEMEMO || '';
    
    // Fetch client discount for the selected client
    fetchClientDiscount(invoice.CLIENT_NO);
    
    // Handle Oracle date format (DD-MON-YY or DD-MON-YYYY)
    let invoiceDate = invoice.INVOICE_DATE;
    if (invoiceDate && invoiceDate.includes('-')) {
        // Convert Oracle date format to YYYY-MM-DD
        let dateParts = invoiceDate.split('-');
        if (dateParts.length === 3) {
            let day = dateParts[0].padStart(2, '0');
            let month = dateParts[1];
            let year = dateParts[2];
            
            // Convert month name to number
            const months = {
                'JAN': '01', 'FEB': '02', 'MAR': '03', 'APR': '04',
                'MAY': '05', 'JUN': '06', 'JUL': '07', 'AUG': '08',
                'SEP': '09', 'OCT': '10', 'NOV': '11', 'DEC': '12'
            };
            
            if (months[month.toUpperCase()]) {
                if (year.length === 2) {
                    year = '20' + year;
                }
                invoiceDate = year + '-' + months[month.toUpperCase()] + '-' + day;
            }
        }
    }
    document.getElementById('invoice_date').value = invoiceDate;
    
    // Clear existing items container
    const itemsContainer = document.getElementById('invoice-items');
    itemsContainer.innerHTML = '';
    
    // Add each detail as an invoice item
    if (details && details.length > 0) {
        details.forEach((detail, index) => {
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
                               step="0.01" min="0" value="${parseFloat(detail.PRICE).toFixed(2)}" required onchange="calculateLineTotal(this)">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Total</label>
                        <input type="text" class="form-control line-total" readonly value="$${(parseFloat(detail.QTY) * parseFloat(detail.PRICE)).toFixed(2)}">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-1" 
                                onclick="removeInvoiceItem(this)" ${details.length === 1 ? 'title="Cannot remove the last item"' : ''}>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            itemsContainer.appendChild(newItem);
        });
    } else {
        // If no details, add one empty item
        addInvoiceItem();
    }
    
    // Calculate the total
    calculateInvoiceTotal();
    
    // Scroll to top of modal content to show loaded items
    const modalBody = document.querySelector('#invoiceModal .modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
}

function viewInvoiceDetails(invoiceId) {
    // Open invoice details in a new window without navbar
    const url = `invoices.php?action=get_invoice_details&id=${invoiceId}`;
    window.open(url, 'invoiceDetails', 'width=800,height=600,scrollbars=yes,resizable=yes');
}

function confirmDelete(id, name) {
    Swal.fire({
        title: 'Confirm Deletion',
        text: `Are you sure you want to delete ${name}? This will also delete all associated invoice details.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `invoices.php?action=delete&id=${encodeURIComponent(id)}`;
        }
    });
}

// Form validation
document.getElementById('invoiceForm').addEventListener('submit', function(e) {
    // Check if there's at least one valid item
    let hasValidItem = false;
    const items = document.querySelectorAll('.invoice-item');
    
    items.forEach(item => {
        const productSelect = item.querySelector('.product-select');
        const quantityInput = item.querySelector('.quantity-input');
        const priceInput = item.querySelector('.price-input');
        
        if (productSelect.value && 
            quantityInput.value && 
            priceInput.value &&
            parseFloat(quantityInput.value) > 0 &&
            parseFloat(priceInput.value) >= 0) {
            hasValidItem = true;
        }
    });
    
    if (!hasValidItem) {
        e.preventDefault();
        alert('An invoice must have at least one valid product item with quantity and price.');
        return false;
    }
});

</script>

<?php
// PHP Functions

function getAllInvoices() {
    try {
        $db = Database::getInstance();
        // Only show invoices that have invoice details (business rule: no invoice without items)
        $sql = "SELECT i.INVOICENO, i.INVOICE_DATE, i.CLIENT_NO, i.EMPLOYEEID, i.INVOICE_STATUS, i.INVOICEMEMO,
                       c.CLIENTNAME as CLIENT_NAME, e.EMPLOYEENAME as EMPLOYEE_NAME,
                       c.DISCOUNT as CLIENT_DISCOUNT,
                       totals.SUBTOTAL_AMOUNT,
                       totals.ITEM_COUNT,
                       ROUND((totals.SUBTOTAL_AMOUNT * NVL(c.DISCOUNT, 0)) / 100, 2) as DISCOUNT_AMOUNT,
                       ROUND(totals.SUBTOTAL_AMOUNT - ((totals.SUBTOTAL_AMOUNT * NVL(c.DISCOUNT, 0)) / 100), 2) as FINAL_TOTAL
                FROM INVOICES i
                LEFT JOIN Clients c ON i.CLIENT_NO = c.CLIENT_NO
                LEFT JOIN Employees e ON i.EMPLOYEEID = e.EMPLOYEEID
                INNER JOIN (
                    SELECT INVOICENO, 
                           SUM(QTY * PRICE) as SUBTOTAL_AMOUNT,
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

function handleDelete($id) {
    try {
        $db = Database::getInstance();
        
        // Delete invoice details first (foreign key constraint)
        $sql = "DELETE FROM INVOICE_DETAILS WHERE INVOICENO = :id";
        $params = [':id' => $id];
        $stmt = $db->query($sql, $params);
        
        // Delete invoice
        $sql = "DELETE FROM INVOICES WHERE INVOICENO = :id";
        $params = [':id' => $id];
        $stmt = $db->query($sql, $params);
        
        $db->commit();
        setFlashMessage('Invoice deleted successfully!', 'success');
        
    } catch (Exception $e) {
        $db = Database::getInstance();
        $db->rollback();
        setFlashMessage('Error deleting invoice: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: invoices.php');
    exit;
}

require_once 'includes/footer.php';
?>
