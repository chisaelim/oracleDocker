<?php
require_once './includes/header.php';
require_once './config/database_oci8.php';
require_once './includes/utils.php';

$success_message = '';
$error_message = '';
$product_no = $_GET['id'] ?? null;

if (!$product_no) {
    header('Location: products.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productname = trim($_POST['productname']);
        $producttype = $_POST['producttype'];
        $sell_price = floatval($_POST['sell_price']);
        $cost_price = floatval($_POST['cost_price']);
        $unit_measure = trim($_POST['unit_measure']);
        $reorder_level = intval($_POST['reorder_level']);
        $qty_on_hand = intval($_POST['qty_on_hand']);
        
        // Calculate profit percentage
        $profit_percent = 0;
        if ($cost_price > 0) {
            $profit_percent = (($sell_price - $cost_price) / $cost_price) * 100;
        }
        
        // Validate required fields
        if (empty($productname) || $sell_price <= 0 || $cost_price <= 0) {
            throw new Exception("Product Name, Sell Price, and Cost Price are required fields.");
        }
        
        // Update product
        $sql = "UPDATE Products SET 
                PRODUCTNAME = :productname, 
                PRODUCTTYPE = :producttype, 
                PROFIT_PERCENT = :profit_percent,
                UNIT_MEASURE = :unit_measure, 
                REORDER_LEVEL = :reorder_level, 
                SELL_PRICE = :sell_price, 
                COST_PRICE = :cost_price, 
                QTY_ON_HAND = :qty_on_hand
                WHERE PRODUCT_NO = :product_no";
        
        $connection = DatabaseOCI8::getConnection();
        $stmt = oci_parse($connection, $sql);
        
        oci_bind_by_name($stmt, ':productname', $productname);
        oci_bind_by_name($stmt, ':producttype', $producttype);
        oci_bind_by_name($stmt, ':profit_percent', $profit_percent);
        oci_bind_by_name($stmt, ':unit_measure', $unit_measure);
        oci_bind_by_name($stmt, ':reorder_level', $reorder_level);
        oci_bind_by_name($stmt, ':sell_price', $sell_price);
        oci_bind_by_name($stmt, ':cost_price', $cost_price);
        oci_bind_by_name($stmt, ':qty_on_hand', $qty_on_hand);
        oci_bind_by_name($stmt, ':product_no', $product_no);
        
        $result = oci_execute($stmt);
        
        if ($result) {
            oci_commit($connection);
            $success_message = "Product updated successfully!";
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

// Get product data
try {
    $product = DatabaseOCI8::queryOne("
        SELECT p.*, pt.PRODUCTTYPE_NAME 
        FROM Products p 
        LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID 
        WHERE p.PRODUCT_NO = :product_no
    ", [':product_no' => $product_no]);
    
    if (!$product) {
        throw new Exception("Product not found.");
    }
} catch (Exception $e) {
    $error_message = "Error loading product: " . $e->getMessage();
    $product = null;
}

// Get product types for dropdown
try {
    $product_types = DatabaseOCI8::query("SELECT PRODUCTTYPE_ID, PRODUCTTYPE_NAME FROM Product_Type ORDER BY PRODUCTTYPE_NAME");
} catch (Exception $e) {
    $product_types = [];
    $error_message = "Error loading product types: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-box-edit me-2"></i>Edit Product
                        <?php if ($product): ?>
                            - <?php echo htmlspecialchars($product['PRODUCTNAME']); ?>
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

                    <?php if ($product): ?>
                    <form method="POST" action="edit_product.php?id=<?php echo urlencode($product_no); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="product_no" class="form-label">
                                    <i class="fas fa-barcode me-1"></i>Product No
                                </label>
                                <input type="text" class="form-control" id="product_no" name="product_no" 
                                       value="<?php echo htmlspecialchars($product['PRODUCT_NO']); ?>" 
                                       readonly style="background-color: #e9ecef;">
                                <small class="form-text text-muted">Product number cannot be changed</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="productname" class="form-label">
                                    <i class="fas fa-tag me-1"></i>Product Name *
                                </label>
                                <input type="text" class="form-control" id="productname" name="productname" 
                                       value="<?php echo htmlspecialchars($_POST['productname'] ?? $product['PRODUCTNAME']); ?>" 
                                       required maxlength="40">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="producttype" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>Product Type
                                </label>
                                <select class="form-select" id="producttype" name="producttype">
                                    <option value="">Select Product Type</option>
                                    <?php foreach ($product_types as $type): ?>
                                        <option value="<?php echo $type['PRODUCTTYPE_ID']; ?>" 
                                                <?php echo (($_POST['producttype'] ?? $product['PRODUCTTYPE']) == $type['PRODUCTTYPE_ID']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['PRODUCTTYPE_NAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="unit_measure" class="form-label">
                                    <i class="fas fa-ruler me-1"></i>Unit of Measure *
                                </label>
                                <input type="text" class="form-control" id="unit_measure" name="unit_measure" 
                                       value="<?php echo htmlspecialchars($_POST['unit_measure'] ?? $product['UNIT_MEASURE']); ?>" 
                                       required maxlength="15" placeholder="e.g., pcs, kg, ltr">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost_price" class="form-label">
                                    <i class="fas fa-dollar-sign me-1"></i>Cost Price *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost_price" name="cost_price" 
                                           value="<?php echo htmlspecialchars($_POST['cost_price'] ?? $product['COST_PRICE']); ?>" 
                                           required min="0" step="0.01" onchange="calculateProfit()">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sell_price" class="form-label">
                                    <i class="fas fa-money-bill-wave me-1"></i>Sell Price *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="sell_price" name="sell_price" 
                                           value="<?php echo htmlspecialchars($_POST['sell_price'] ?? $product['SELL_PRICE']); ?>" 
                                           required min="0" step="0.01" onchange="calculateProfit()">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="profit_display" class="form-label">
                                    <i class="fas fa-chart-line me-1"></i>Profit Margin
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="profit_display" readonly 
                                           style="background-color: #e9ecef;" placeholder="Auto-calculated">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="qty_on_hand" class="form-label">
                                    <i class="fas fa-cubes me-1"></i>Quantity on Hand
                                </label>
                                <input type="number" class="form-control" id="qty_on_hand" name="qty_on_hand" 
                                       value="<?php echo htmlspecialchars($_POST['qty_on_hand'] ?? $product['QTY_ON_HAND']); ?>" 
                                       min="0">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="reorder_level" class="form-label">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Reorder Level *
                                </label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level" 
                                       value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? $product['REORDER_LEVEL']); ?>" 
                                       required min="0">
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="products.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Products
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Product
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <div class="text-muted">Product not found or could not be loaded.</div>
                            <a href="products.php" class="btn btn-primary mt-3">
                                <i class="fas fa-arrow-left me-1"></i>Back to Products
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculateProfit() {
    const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
    const sellPrice = parseFloat(document.getElementById('sell_price').value) || 0;
    
    if (costPrice > 0) {
        const profitPercent = ((sellPrice - costPrice) / costPrice) * 100;
        document.getElementById('profit_display').value = profitPercent.toFixed(2);
        
        // Update display color based on profit margin
        const profitDisplay = document.getElementById('profit_display');
        if (profitPercent < 10) {
            profitDisplay.style.color = '#dc3545'; // red
        } else if (profitPercent < 25) {
            profitDisplay.style.color = '#fd7e14'; // orange
        } else {
            profitDisplay.style.color = '#198754'; // green
        }
    } else {
        document.getElementById('profit_display').value = '';
    }
}

// Calculate profit on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateProfit();
});
</script>

<?php require_once './includes/footer.php'; ?>