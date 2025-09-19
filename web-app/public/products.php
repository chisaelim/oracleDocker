<?php
// products.php - Products Management with CRUD Operations and Image Upload
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/utils.php';


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleFormSubmission();
}

// Handle delete requests
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['product_no'])) {
    handleDelete($_GET['product_no']);
}

// Handle edit requests
$editProduct = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editProduct = getProductById($_GET['id']);
}

// Get all products and product types for display
$products = getAllProducts();
$productTypes = getAllProductTypes();

function handleFormSubmission()
{
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
        return;
    }

    $action = $_POST['action'];

    if ($action === 'create') {
        createProduct($_POST, $_FILES);
    } elseif ($action === 'update') {
        updateProduct($_POST, $_FILES);
    }
}

function handleDelete($productNo)
{
    try {
        $db = Database::getInstance();

        // Get the image filename before deleting
        $imageSql = "SELECT PHOTO FROM Products WHERE PRODUCT_NO = :product_no";
        $imageStmt = oci_parse($db->getConnection(), $imageSql);
        oci_bind_by_name($imageStmt, ':product_no', $productNo);
        oci_execute($imageStmt);
        $imageResult = oci_fetch_assoc($imageStmt);
        oci_free_statement($imageStmt);

        // Delete the product
        $sql = "DELETE FROM Products WHERE PRODUCT_NO = :product_no";
        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':product_no', $productNo);

        if (oci_execute($stmt)) {
            // Delete the image file if it exists
            if ($imageResult && !empty($imageResult['PHOTO'])) {
                $imagePath = 'uploads/products/' . $imageResult['PHOTO'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            setFlashMessage('Product deleted successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error deleting product: ' . $error['message'], 'danger');
        }

        oci_free_statement($stmt);

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }

    header('Location: products.php');
    exit();
}

function getProductById($productNo)
{
    try {
        $db = Database::getInstance();
        $sql = "SELECT p.PRODUCT_NO, p.PRODUCTNAME, p.PRODUCTTYPE, p.PROFIT_PERCENT, 
                       p.UNIT_MEASURE, p.REORDER_LEVEL, p.SELL_PRICE, p.COST_PRICE, 
                       p.QTY_ON_HAND, p.PHOTO, pt.PRODUCTTYPE_NAME
                FROM Products p
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                WHERE p.PRODUCT_NO = :product_no";

        $stmt = oci_parse($db->getConnection(), $sql);
        oci_bind_by_name($stmt, ':product_no', $productNo);
        oci_execute($stmt);
        $result = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $result;

    } catch (Exception $e) {
        setFlashMessage('Error fetching product: ' . $e->getMessage(), 'danger');
        return null;
    }
}

function getAllProducts()
{
    try {
        $db = Database::getInstance();
        $sql = "SELECT p.PRODUCT_NO, p.PRODUCTNAME, p.PRODUCTTYPE, p.PROFIT_PERCENT, 
                       p.UNIT_MEASURE, p.REORDER_LEVEL, p.SELL_PRICE, p.COST_PRICE, 
                       p.QTY_ON_HAND, p.PHOTO, pt.PRODUCTTYPE_NAME
                FROM Products p
                LEFT JOIN Product_Type pt ON p.PRODUCTTYPE = pt.PRODUCTTYPE_ID
                ORDER BY p.PRODUCT_NO";

        $stmt = oci_parse($db->getConnection(), $sql);
        oci_execute($stmt);

        $products = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $products[] = $row;
        }

        oci_free_statement($stmt);
        return $products;

    } catch (Exception $e) {
        setFlashMessage('Error fetching products: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function getAllProductTypes()
{
    try {
        $db = Database::getInstance();
        $sql = "SELECT PRODUCTTYPE_ID, PRODUCTTYPE_NAME FROM Product_Type ORDER BY PRODUCTTYPE_NAME";

        $stmt = oci_parse($db->getConnection(), $sql);
        oci_execute($stmt);

        $productTypes = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $productTypes[] = $row;
        }

        oci_free_statement($stmt);
        return $productTypes;

    } catch (Exception $e) {
        setFlashMessage('Error fetching product types: ' . $e->getMessage(), 'danger');
        return [];
    }
}

function createProduct($data, $files)
{
    try {
        // Validate required fields
        if (empty($data['product_no']) || empty($data['product_name']) || empty($data['product_type'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }

        // Extract and validate data
        $productNo = trim($data['product_no']);
        $productName = trim($data['product_name']);
        $productType = (int) $data['product_type'];
        $unitMeasure = trim($data['unit_measure'] ?? '');
        $reorderLevel = (int) ($data['reorder_level'] ?? 0);
        $sellPrice = floatval($data['sell_price'] ?? 0);
        $costPrice = floatval($data['cost_price'] ?? 0);
        $qtyOnHand = (int) ($data['qty_on_hand'] ?? 0);

        // Validation
        if (strlen($productNo) > 20) {
            setFlashMessage('Product Number must be 20 characters or less.', 'danger');
            return;
        }

        if (strlen($productName) > 50) {
            setFlashMessage('Product Name must be 50 characters or less.', 'danger');
            return;
        }

        if ($sellPrice < 0 || $costPrice < 0) {
            setFlashMessage('Prices cannot be negative.', 'danger');
            return;
        }

        $db = Database::getInstance();

        // Check if product number already exists
        $checkSql = "SELECT COUNT(*) as count FROM Products WHERE UPPER(PRODUCT_NO) = UPPER(:product_no)";
        $checkStmt = oci_parse($db->getConnection(), $checkSql);
        oci_bind_by_name($checkStmt, ':product_no', $productNo);
        oci_execute($checkStmt);
        $result = oci_fetch_assoc($checkStmt);
        oci_free_statement($checkStmt);

        if ($result && $result['COUNT'] > 0) {
            setFlashMessage('Product Number already exists.', 'danger');
            return;
        }

        // Check if product name already exists
        $checkNameSql = "SELECT COUNT(*) as count FROM Products WHERE UPPER(PRODUCTNAME) = UPPER(:product_name)";
        $checkNameStmt = oci_parse($db->getConnection(), $checkNameSql);
        oci_bind_by_name($checkNameStmt, ':product_name', $productName);
        oci_execute($checkNameStmt);
        $nameResult = oci_fetch_assoc($checkNameStmt);
        oci_free_statement($checkNameStmt);

        if ($nameResult && $nameResult['COUNT'] > 0) {
            setFlashMessage('Product Name already exists.', 'danger');
            return;
        }

        // Calculate profit percentage from cost and sell price
        $profitPercent = 0;
        if ($costPrice > 0) {
            $profitPercent = (($sellPrice - $costPrice) / $costPrice) * 100;
        }

        // Handle image upload
        $imageFilename = null;
        if (isset($files['product_image']) && $files['product_image']['error'] === UPLOAD_ERR_OK) {
            $imageFilename = processImageUpload($files['product_image'], $productNo);
        }

        // Insert new product
        $sql = "INSERT INTO Products (PRODUCT_NO, PRODUCTNAME, PRODUCTTYPE, PROFIT_PERCENT, 
                                     UNIT_MEASURE, REORDER_LEVEL, SELL_PRICE, COST_PRICE, 
                                     QTY_ON_HAND, PHOTO) 
                VALUES (:product_no, :product_name, :product_type, :profit_percent, 
                        :unit_measure, :reorder_level, :sell_price, :cost_price, 
                        :qty_on_hand, :photo)";

        $stmt = oci_parse($db->getConnection(), $sql);

        oci_bind_by_name($stmt, ':product_no', $productNo);
        oci_bind_by_name($stmt, ':product_name', $productName);
        oci_bind_by_name($stmt, ':product_type', $productType);
        oci_bind_by_name($stmt, ':profit_percent', $profitPercent);
        oci_bind_by_name($stmt, ':unit_measure', $unitMeasure);
        oci_bind_by_name($stmt, ':reorder_level', $reorderLevel);
        oci_bind_by_name($stmt, ':sell_price', $sellPrice);
        oci_bind_by_name($stmt, ':cost_price', $costPrice);
        oci_bind_by_name($stmt, ':qty_on_hand', $qtyOnHand);
        oci_bind_by_name($stmt, ':photo', $imageFilename);

        if (oci_execute($stmt)) {
            setFlashMessage('Product created successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error creating product: ' . $error['message'], 'danger');
        }

        oci_free_statement($stmt);

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }

    header('Location: products.php');
    exit();
}

function updateProduct($data, $files)
{
    try {
        // Validate required fields
        if (empty($data['product_no']) || empty($data['product_name']) || empty($data['product_type']) || empty($data['original_product_no'])) {
            setFlashMessage('Please fill in all required fields.', 'danger');
            return;
        }

        // Extract and validate data
        $productNo = trim($data['product_no']);
        $productName = trim($data['product_name']);
        $productType = (int) $data['product_type'];
        $unitMeasure = trim($data['unit_measure'] ?? '');
        $reorderLevel = (int) ($data['reorder_level'] ?? 0);
        $sellPrice = floatval($data['sell_price'] ?? 0);
        $costPrice = floatval($data['cost_price'] ?? 0);
        $qtyOnHand = (int) ($data['qty_on_hand'] ?? 0);
        $originalProductNo = trim($data['original_product_no']);

        // Validation
        if (strlen($productNo) > 20) {
            setFlashMessage('Product Number must be 20 characters or less.', 'danger');
            return;
        }

        if (strlen($productName) > 50) {
            setFlashMessage('Product Name must be 50 characters or less.', 'danger');
            return;
        }

        if ($sellPrice < 0 || $costPrice < 0) {
            setFlashMessage('Prices cannot be negative.', 'danger');
            return;
        }

        $db = Database::getInstance();

        // Check if product number already exists (excluding current record)
        if ($productNo !== $originalProductNo) {
            $checkSql = "SELECT COUNT(*) as count FROM Products WHERE UPPER(PRODUCT_NO) = UPPER(:product_no)";
            $checkStmt = oci_parse($db->getConnection(), $checkSql);
            oci_bind_by_name($checkStmt, ':product_no', $productNo);
            oci_execute($checkStmt);
            $result = oci_fetch_assoc($checkStmt);
            oci_free_statement($checkStmt);

            if ($result && $result['COUNT'] > 0) {
                setFlashMessage('Product Number already exists.', 'danger');
                return;
            }
        }

        // Check if product name already exists (excluding current record)
        $checkNameSql = "SELECT COUNT(*) as count FROM Products 
                         WHERE UPPER(PRODUCTNAME) = UPPER(:product_name) 
                         AND PRODUCT_NO != :original_product_no";
        $checkNameStmt = oci_parse($db->getConnection(), $checkNameSql);
        oci_bind_by_name($checkNameStmt, ':product_name', $productName);
        oci_bind_by_name($checkNameStmt, ':original_product_no', $originalProductNo);
        oci_execute($checkNameStmt);
        $nameResult = oci_fetch_assoc($checkNameStmt);
        oci_free_statement($checkNameStmt);

        if ($nameResult && $nameResult['COUNT'] > 0) {
            setFlashMessage('Product Name already exists.', 'danger');
            return;
        }

        // Calculate profit percentage from cost and sell price
        $profitPercent = 0;
        if ($costPrice > 0) {
            $profitPercent = (($sellPrice - $costPrice) / $costPrice) * 100;
        }

        // Handle image upload if provided
        $imageFilename = null;
        $updateImage = false;
        if (isset($files['product_image']) && $files['product_image']['error'] === UPLOAD_ERR_OK) {
            // Get old image filename to delete it later
            $oldImageSql = "SELECT PHOTO FROM Products WHERE PRODUCT_NO = :original_product_no";
            $oldImageStmt = oci_parse($db->getConnection(), $oldImageSql);
            oci_bind_by_name($oldImageStmt, ':original_product_no', $originalProductNo);
            oci_execute($oldImageStmt);
            $oldImageResult = oci_fetch_assoc($oldImageStmt);
            oci_free_statement($oldImageStmt);

            $imageFilename = processImageUpload($files['product_image'], $productNo);
            $updateImage = true;

            // Delete old image file if it exists
            if ($oldImageResult && $oldImageResult['PHOTO'] && !empty($oldImageResult['PHOTO'])) {
                $oldImagePath = 'uploads/products/' . $oldImageResult['PHOTO'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }

        // Update product
        if ($updateImage) {
            $sql = "UPDATE Products 
                    SET PRODUCT_NO = :product_no, PRODUCTNAME = :product_name, 
                        PRODUCTTYPE = :product_type, PROFIT_PERCENT = :profit_percent,
                        UNIT_MEASURE = :unit_measure, REORDER_LEVEL = :reorder_level,
                        SELL_PRICE = :sell_price, COST_PRICE = :cost_price,
                        QTY_ON_HAND = :qty_on_hand, PHOTO = :photo
                    WHERE PRODUCT_NO = :original_product_no";
        } else {
            $sql = "UPDATE Products 
                    SET PRODUCT_NO = :product_no, PRODUCTNAME = :product_name, 
                        PRODUCTTYPE = :product_type, PROFIT_PERCENT = :profit_percent,
                        UNIT_MEASURE = :unit_measure, REORDER_LEVEL = :reorder_level,
                        SELL_PRICE = :sell_price, COST_PRICE = :cost_price,
                        QTY_ON_HAND = :qty_on_hand
                    WHERE PRODUCT_NO = :original_product_no";
        }

        $stmt = oci_parse($db->getConnection(), $sql);

        oci_bind_by_name($stmt, ':product_no', $productNo);
        oci_bind_by_name($stmt, ':product_name', $productName);
        oci_bind_by_name($stmt, ':product_type', $productType);
        oci_bind_by_name($stmt, ':profit_percent', $profitPercent);
        oci_bind_by_name($stmt, ':unit_measure', $unitMeasure);
        oci_bind_by_name($stmt, ':reorder_level', $reorderLevel);
        oci_bind_by_name($stmt, ':sell_price', $sellPrice);
        oci_bind_by_name($stmt, ':cost_price', $costPrice);
        oci_bind_by_name($stmt, ':qty_on_hand', $qtyOnHand);
        oci_bind_by_name($stmt, ':original_product_no', $originalProductNo);

        if ($updateImage) {
            oci_bind_by_name($stmt, ':photo', $imageFilename);
        }

        if (oci_execute($stmt)) {
            setFlashMessage('Product updated successfully!', 'success');
        } else {
            $error = oci_error($stmt);
            setFlashMessage('Error updating product: ' . $error['message'], 'danger');
        }

        oci_free_statement($stmt);

    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'danger');
    }

    header('Location: products.php');
    exit();
}

function processImageUpload($file, $productNo)
{
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid image type. Only JPG, PNG, and GIF are allowed.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('Image size too large. Maximum size is 5MB.');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $productNo . '_' . time() . '.' . strtolower($extension);
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload image file.');
    }

    return $filename;
}

function getProductPhotoPath($productNo)
{
    $db = Database::getInstance();

    $sql = "SELECT PHOTO FROM Products WHERE PRODUCT_NO = :product_no";
    $stmt = oci_parse($db->getConnection(), $sql);
    oci_bind_by_name($stmt, ':product_no', $productNo);
    oci_execute($stmt);

    $result = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    if ($result && $result['PHOTO'] && !empty($result['PHOTO'])) {
        $imagePath = 'uploads/products/' . $result['PHOTO'];

        if (file_exists($imagePath)) {
            return 'uploads/products/' . $result['PHOTO'];
        }
    }
    return 'uploads/empty.png';
}

// Data already fetched at the top of the file
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-box me-2"></i>Products Management
                    </h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="fas fa-plus me-1"></i>Add Product
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Products Found</h5>
                            <p class="text-muted">Start by adding your first product.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#productModal">
                                <i class="fas fa-plus me-1"></i>
                                Add Product
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover data-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product No</th>
                                        <th>Product Name</th>
                                        <th>Type</th>
                                        <th>Sell Price</th>
                                        <th>Qty on Hand</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <img src="<?= getProductPhotoPath(htmlspecialchars($product['PRODUCT_NO'])) ?>"
                                                    alt="Product Image"
                                                    style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td><?= htmlspecialchars($product['PRODUCT_NO']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($product['PRODUCTNAME']) ?></strong>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-info"><?= htmlspecialchars($product['PRODUCTTYPE_NAME'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>$<?= number_format($product['SELL_PRICE'], 2) ?></td>
                                            <td>
                                                <?php if ($product['QTY_ON_HAND'] <= 0): ?>
                                                    <span class="badge bg-danger"><?= $product['QTY_ON_HAND'] ?> (Out of
                                                        Stock)</span>
                                                <?php elseif ($product['QTY_ON_HAND'] <= $product['REORDER_LEVEL']): ?>
                                                    <span class="badge bg-warning"><?= $product['QTY_ON_HAND'] ?> (Low Stock)</span>
                                                <?php else: ?>
                                                    <?= $product['QTY_ON_HAND'] ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        onclick="editProduct('<?= htmlspecialchars($product['PRODUCT_NO'], ENT_QUOTES) ?>')"
                                                        data-bs-toggle="tooltip" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="confirmDelete('<?= htmlspecialchars($product['PRODUCT_NO'], ENT_QUOTES) ?>', '<?= htmlspecialchars($product['PRODUCTNAME'], ENT_QUOTES) ?>')"
                                                        data-bs-toggle="tooltip" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
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

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel"><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productForm" method="POST" action="products.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
                    <input type="hidden" name="original_product_no"
                        value="<?= $editProduct ? htmlspecialchars($editProduct['PRODUCT_NO']) : '' ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_no" class="form-label">Product Number <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_no" name="product_no" maxlength="20"
                                    value="<?= $editProduct ? htmlspecialchars($editProduct['PRODUCT_NO']) : '' ?>"
                                    required>
                                <div class="form-text">Unique product identifier (max 20 characters)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_name" class="form-label">Product Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_name" name="product_name"
                                    maxlength="40"
                                    value="<?= $editProduct ? htmlspecialchars($editProduct['PRODUCTNAME']) : '' ?>"
                                    required>
                                <div class="form-text">Maximum 40 characters</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_type" class="form-label">Product Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="product_type" name="product_type" required>
                                    <option value="">Select Product Type</option>
                                    <?php foreach ($productTypes as $type): ?>
                                        <option value="<?= $type['PRODUCTTYPE_ID'] ?>" <?= ($editProduct && $editProduct['PRODUCTTYPE'] == $type['PRODUCTTYPE_ID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($type['PRODUCTTYPE_NAME']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_measure" class="form-label">Unit of Measure <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="unit_measure" name="unit_measure"
                                    maxlength="15" placeholder="pieces, kg, liters, etc."
                                    value="<?= $editProduct ? htmlspecialchars($editProduct['UNIT_MEASURE']) : '' ?>"
                                    required>
                                <div class="form-text">Maximum 15 characters</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Cost Price <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost_price" name="cost_price" min="0"
                                        step="0.01"
                                        value="<?= $editProduct ? htmlspecialchars($editProduct['COST_PRICE']) : '' ?>"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="sell_price" class="form-label">Sell Price <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="sell_price" name="sell_price" min="0"
                                        step="0.01"
                                        value="<?= $editProduct ? htmlspecialchars($editProduct['SELL_PRICE']) : '' ?>"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="profit_percent" class="form-label">Profit % <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="profit_percent" name="profit_percent"
                                        min="0" max="100" step="0.01"
                                        value="<?= $editProduct ? htmlspecialchars($editProduct['PROFIT_PERCENT']) : '' ?>"
                                        readonly>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Auto-calculated from cost and sell price</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="qty_on_hand" class="form-label">Quantity on Hand</label>
                                <input type="number" class="form-control" id="qty_on_hand" name="qty_on_hand" min="0"
                                    value="<?= $editProduct ? htmlspecialchars($editProduct['QTY_ON_HAND']) : '0' ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reorder_level" class="form-label">Reorder Level <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="reorder_level" name="reorder_level"
                                    min="0"
                                    value="<?= $editProduct ? htmlspecialchars($editProduct['REORDER_LEVEL']) : '' ?>"
                                    required>
                                <div class="form-text">Alert when stock falls below this level</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="product_image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="product_image" name="product_image"
                            accept="image/jpeg,image/jpg,image/png,image/gif">
                        <div class="form-text">Optional. Max 5MB. Supported formats: JPG, PNG, GIF</div>
                        <div id="imagePreview" class="mt-2"
                            style="<?= ($editProduct && $editProduct['PHOTO']) ? 'display: block;' : 'display: none;' ?>">
                            <img id="previewImg"
                                src="<?= ($editProduct && $editProduct['PHOTO']) ? 'uploads/products/' . htmlspecialchars($editProduct['PHOTO']) : '' ?>"
                                alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i><?= $editProduct ? 'Update Product' : 'Save Product' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editProduct): ?>
    <script>
        // Show modal for edit mode
        document.addEventListener('DOMContentLoaded', function () {
            var modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        });
    </script>
<?php endif; ?>

<script>
    function resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('productModalLabel').textContent = 'Add Product';
        document.querySelector('input[name="action"]').value = 'create';
        document.querySelector('input[name="original_product_no"]').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        calculateProfitPercent();
    }

    function editProduct(productNo) {
        // Make a request to get the product data
        window.location.href = `products.php?action=edit&id=${encodeURIComponent(productNo)}`;
    }

    function confirmDelete(productNo, productName) {

        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete product "${productName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `products.php?action=delete&product_no=${encodeURIComponent(productNo)}`;
            }
        });
    }

    // Auto-calculate profit percentage
    function calculateProfitPercent() {
        const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
        const sellPrice = parseFloat(document.getElementById('sell_price').value) || 0;

        if (costPrice > 0) {
            const profitPercent = ((sellPrice - costPrice) / costPrice) * 100;
            document.getElementById('profit_percent').value = profitPercent.toFixed(2);
        } else {
            document.getElementById('profit_percent').value = '0.00';
        }
    }

    // Image preview
    function handleImagePreview(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            document.getElementById('imagePreview').style.display = 'none';
        }
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-calculate profit on input
        const costPriceInput = document.getElementById('cost_price');
        const sellPriceInput = document.getElementById('sell_price');

        if (costPriceInput) costPriceInput.addEventListener('input', calculateProfitPercent);
        if (sellPriceInput) sellPriceInput.addEventListener('input', calculateProfitPercent);

        // Image preview on change
        const imageInput = document.getElementById('product_image');
        if (imageInput) {
            imageInput.addEventListener('change', function () {
                handleImagePreview(this);
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>