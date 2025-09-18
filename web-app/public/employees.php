<?php
/**
 * Employees Management (Placeholder)
 */

require_once 'config/config.php';
require_once 'includes/utils.php';

$pageTitle = 'Employees Management';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-tie me-2"></i>Employees Management
                    </h4>
                    <button type="button" class="btn btn-light" disabled>
                        <i class="fas fa-plus me-1"></i>Add New Employee
                    </button>
                </div>
                <div class="card-body text-center py-5">
                <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Employees Management</h4>
                <p class="text-muted">This page will contain employee management functionality.</p>
                <p class="text-muted">
                    <strong>Features will include:</strong><br>
                    • View all employees<br>
                    • Add new employees<br>
                    • Edit employee information<br>
                    • Manage job assignments<br>
                    • Track salaries and performance
                </p>
                <div class="mt-4">
                    <a href="client_types.php" class="btn btn-primary me-2">
                        <i class="fas fa-tags me-1"></i>
                        Start with Client Types
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-1"></i>
                        Back to Dashboard
                    </a>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>