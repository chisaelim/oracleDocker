<?php
$page_title = 'PHP Information';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>
            <i class="fas fa-info-circle me-3"></i>
            PHP Environment Information
        </h1>
        <p class="lead mb-0">PHP Configuration and Extension Status</p>
    </div>
</div>

<!-- PHP Version and Extensions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fab fa-php me-2"></i>
                    PHP Information
                </h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">PHP Version:</dt>
                    <dd class="col-sm-8"><?php echo PHP_VERSION; ?></dd>
                    
                    <dt class="col-sm-4">Server API:</dt>
                    <dd class="col-sm-8"><?php echo php_sapi_name(); ?></dd>
                    
                    <dt class="col-sm-4">System:</dt>
                    <dd class="col-sm-8"><?php echo php_uname(); ?></dd>
                    
                    <dt class="col-sm-4">Memory Limit:</dt>
                    <dd class="col-sm-8"><?php echo ini_get('memory_limit'); ?></dd>
                    
                    <dt class="col-sm-4">Max Execution Time:</dt>
                    <dd class="col-sm-8"><?php echo ini_get('max_execution_time'); ?> seconds</dd>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-puzzle-piece me-2"></i>
                    Oracle Extensions Status
                </h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        OCI8 Extension
                        <?php if (extension_loaded('oci8')): ?>
                            <span class="badge bg-success">Loaded</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Loaded</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        PDO_OCI Extension
                        <?php if (extension_loaded('pdo_oci')): ?>
                            <span class="badge bg-success">Loaded</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Loaded</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        PDO Extension
                        <?php if (extension_loaded('pdo')): ?>
                            <span class="badge bg-success">Loaded</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Loaded</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!extension_loaded('oci8') && !extension_loaded('pdo_oci')): ?>
                    <div class="alert alert-warning mt-3">
                        <strong>Oracle Extensions Not Available</strong><br>
                        To connect to Oracle Database, you need to install PHP Oracle extensions.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Environment Variables -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cogs me-2"></i>
                    Oracle Environment Variables
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Variable</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>ORACLE_HOME</code></td>
                                <td><?php echo getenv('ORACLE_HOME') ?: 'Not Set'; ?></td>
                                <td>
                                    <?php if (getenv('ORACLE_HOME')): ?>
                                        <span class="badge bg-success">Set</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Not Set</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><code>LD_LIBRARY_PATH</code></td>
                                <td><?php echo getenv('LD_LIBRARY_PATH') ?: 'Not Set'; ?></td>
                                <td>
                                    <?php if (getenv('LD_LIBRARY_PATH')): ?>
                                        <span class="badge bg-success">Set</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Not Set</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><code>PATH</code></td>
                                <td><?php echo substr(getenv('PATH'), 0, 100) . '...'; ?></td>
                                <td><span class="badge bg-info">Truncated</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loaded Extensions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    All Loaded PHP Extensions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php 
                    $extensions = get_loaded_extensions();
                    sort($extensions);
                    $chunks = array_chunk($extensions, ceil(count($extensions) / 3));
                    foreach ($chunks as $chunk): 
                    ?>
                        <div class="col-md-4">
                            <ul class="list-unstyled">
                                <?php foreach ($chunk as $ext): ?>
                                    <li>
                                        <span class="badge bg-light text-dark me-2"><?php echo $ext; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>