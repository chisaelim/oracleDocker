/**
 * Main JavaScript application
 */

$(document).ready(function() {
    // Initialize DataTables
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "No entries available",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }
    
    // Form validation
    $('.needs-validation').on('submit', function(e) {
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // Auto-hide alerts after 5 seconds
    $('.alert:not(.alert-permanent)').delay(5000).fadeOut(500);
    
    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const itemName = $(this).data('item-name') || 'this item';
        
        Swal.fire({
            title: 'Are you sure?',
            text: `You won't be able to revert the deletion of ${itemName}!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
    
    // Loading overlay functions
    window.showLoading = function() {
        $('body').append('<div class="spinner-overlay"><div class="spinner-border spinner-border-lg text-light" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    };
    
    window.hideLoading = function() {
        $('.spinner-overlay').remove();
    };
    
    // AJAX form submissions
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const url = form.attr('action');
        const method = form.attr('method') || 'POST';
        const formData = new FormData(this);
        
        showLoading();
        
        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message || 'Operation completed successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'An error occurred',
                        icon: 'error'
                    });
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                console.error('AJAX Error:', error);
                
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred. Please try again.',
                    icon: 'error'
                });
            }
        });
    });
    
    // Number formatting for currency fields
    $('.currency-input').on('input', function() {
        let value = $(this).val().replace(/[^\d.]/g, '');
        if (value) {
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            $(this).val(value);
        }
    });
    
    // Percentage input formatting
    $('.percentage-input').on('input', function() {
        let value = $(this).val().replace(/[^\d.]/g, '');
        if (value) {
            const num = parseFloat(value);
            if (num > 100) {
                $(this).val('100');
            }
        }
    });
    
    // Phone number formatting
    $('.phone-input').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 6) {
            if (value.length <= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d{1,4})/, '$1-$2-$3');
            } else {
                value = value.substring(0, 10).replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
            }
        }
        $(this).val(value);
    });
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
    
    // Auto-resize textareas
    $('textarea.auto-resize').on('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
    
    // Search functionality for dropdowns
    $('.searchable-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select an option...',
        allowClear: true
    });
    
    // Copy to clipboard functionality
    $('.copy-btn').on('click', function(e) {
        e.preventDefault();
        const text = $(this).data('copy-text') || $(this).siblings('input').val();
        navigator.clipboard.writeText(text).then(() => {
            const btn = $(this);
            const originalText = btn.html();
            btn.html('<i class="fas fa-check"></i> Copied!');
            setTimeout(() => {
                btn.html(originalText);
            }, 2000);
        });
    });
});

// Utility functions
const Utils = {
    // Format currency
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },
    
    // Format percentage
    formatPercentage: function(value) {
        return parseFloat(value).toFixed(2) + '%';
    },
    
    // Format date
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    // Debounce function
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    // Validate email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Validate phone
    validatePhone: function(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/\D/g, ''));
    }
};

// Export to global scope
window.Utils = Utils;