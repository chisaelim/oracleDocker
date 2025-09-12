<?php
// Common header for all pages
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo AppConfig::APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-database me-2"></i>
                <?php echo AppConfig::APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="clientsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Clients
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="clients.php">View Clients</a></li>
                            <li><a class="dropdown-item" href="add_client.php">Add Client</a></li>
                            <li><a class="dropdown-item" href="client_types.php">Client Types</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="productsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-box me-1"></i>Products
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="products.php">View Products</a></li>
                            <li><a class="dropdown-item" href="add_product.php">Add Product</a></li>
                            <li><a class="dropdown-item" href="product_types.php">Product Types</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="employeesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-tie me-1"></i>Employees
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="employees.php">View Employees</a></li>
                            <li><a class="dropdown-item" href="add_employee.php">Add Employee</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="jobsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-briefcase me-1"></i>Jobs
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="jobs.php">View Jobs</a></li>
                            <li><a class="dropdown-item" href="add_job.php">Add Job</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="invoicesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-invoice me-1"></i>Invoices
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="invoices.php">View Invoices</a></li>
                            <li><a class="dropdown-item" href="add_invoice.php">Create Invoice</a></li>
                        </ul>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="database_info.php">
                            <i class="fas fa-info-circle me-1"></i>DB Info
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="phpinfo.php">
                            <i class="fab fa-php me-1"></i>PHP Info
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>