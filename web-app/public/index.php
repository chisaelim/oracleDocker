<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once 'includes/utils.php';

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
        
        // Get statistics using OCI8
        $stats = [];
        
        // Count clients
        $result = DatabaseOCI8::queryOne("SELECT COUNT(*) as count FROM Clients");
        $stats['clients'] = $result['COUNT'];
        
        // Count products
        $result = DatabaseOCI8::queryOne("SELECT COUNT(*) as count FROM Products");
        $stats['products'] = $result['COUNT'];
        
        // Count employees
        $result = DatabaseOCI8::queryOne("SELECT COUNT(*) as count FROM Employees");  
        $stats['employees'] = $result['COUNT'];
        
        // Count invoices
        $result = DatabaseOCI8::queryOne("SELECT COUNT(*) as count FROM Invoices");
        $stats['invoices'] = $result['COUNT'];
        
        // Recent clients
        $recent_clients = DatabaseOCI8::query("
            SELECT CLIENT_NO, CLIENTNAME, CITY, PHONE 
            FROM (
                SELECT CLIENT_NO, CLIENTNAME, CITY, PHONE 
                FROM Clients 
                ORDER BY CLIENT_NO DESC
            ) 
            WHERE ROWNUM <= 5
        ");
        
        // Recent products
        $recent_products = DatabaseOCI8::query("
            SELECT PRODUCT_NO, PRODUCTNAME as PRODUCT_NAME, SELL_PRICE as PRICE, QTY_ON_HAND 
            FROM (
                SELECT PRODUCT_NO, PRODUCTNAME, SELL_PRICE, QTY_ON_HAND 
                FROM Products 
                ORDER BY PRODUCT_NO DESC
            ) 
            WHERE ROWNUM <= 5
        ");
        
        $connection_test = DatabaseOCI8::testConnection();
    } else {
        // No OCI8 extension available
        $stats = ['clients' => 0, 'products' => 0, 'employees' => 0, 'invoices' => 0];
        $recent_clients = [];
        $recent_products = [];
        $connection_test = ['success' => false, 'message' => 'OCI8 extension not available'];
    }
    
} catch (Exception $e) {
    Utils::setErrorMessage("Database error: " . $e->getMessage());
    $stats = ['clients' => 0, 'products' => 0, 'employees' => 0, 'invoices' => 0];
    $recent_clients = [];
    $recent_products = [];
    $connection_test = ['success' => false, 'message' => $e->getMessage()];
}
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    Dashboard
                </h1>
                <p class="lead mb-0">Oracle Business Management System Overview</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="connection-status <?php echo $connection_test['success'] ? 'connected' : 'disconnected'; ?>">
                    <i class="fas fa-<?php echo $connection_test['success'] ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo $connection_test['success'] ? 'Connected' : 'Disconnected'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x mb-3"></i>
                <h3><?php echo number_format($stats['clients']); ?></h3>
                <p>Total Clients</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-center">
                <i class="fas fa-box fa-2x mb-3"></i>
                <h3><?php echo number_format($stats['products']); ?></h3>
                <p>Total Products</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="card-body text-center">
                <i class="fas fa-user-tie fa-2x mb-3"></i>
                <h3><?php echo number_format($stats['employees']); ?></h3>
                <p>Total Employees</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice fa-2x mb-3"></i>
                <h3><?php echo number_format($stats['invoices']); ?></h3>
                <p>Total Invoices</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Data -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Recent Clients
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_clients)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clients as $client): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client['CLIENT_NO'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($client['CLIENTNAME'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($client['CITY'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($client['PHONE'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="clients.php" class="btn btn-outline-primary btn-sm">
                            View All Clients <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No clients found</p>
                        <a href="add_client.php" class="btn btn-primary">Add First Client</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>
                    Recent Products
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_products)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['PRODUCT_NO'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($product['PRODUCT_NAME'] ?? ''); ?></td>
                                        <td><?php echo Utils::formatCurrency($product['PRICE'] ?? 0); ?></td>
                                        <td>
                                            <?php $qty = $product['QTY_ON_HAND'] ?? 0; ?>
                                            <span class="badge <?php echo $qty < 10 ? 'bg-warning' : 'bg-success'; ?>">
                                                <?php echo number_format($qty); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <a href="products.php" class="btn btn-outline-primary btn-sm">
                            View All Products <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No products found</p>
                        <a href="add_product.php" class="btn btn-primary">Add First Product</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="add_client.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Add Client
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="add_product.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-plus me-2"></i>
                            Add Product
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="add_employee.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-user-tie me-2"></i>
                            Add Employee
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="add_invoice.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            Create Invoice
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>