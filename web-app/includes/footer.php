</div> <!-- End Container -->

<!-- Footer -->
<footer class="bg-light mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0 text-muted">
                    <i class="fas fa-copyright me-1"></i>
                    <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">
                    <i class="fas fa-server me-1"></i>
                    Oracle Database XE 21c
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- JavaScript Libraries -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<!-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script> -->

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 for better alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Custom JavaScript -->
<script src="assets/js/app.js"></script>

<?php if (isset($customJS)): ?>
    <!-- Page-specific JavaScript -->
    <?= $customJS ?>
<?php endif; ?>
</body>

</html>