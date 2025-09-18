<?php
/**
 * Common utility functions
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 */
function validateRequired($data, $required_fields) {
    $errors = [];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format percentage
 */
function formatPercentage($value) {
    return number_format($value, 2) . '%';
}

/**
 * Generate pagination
 */
function generatePagination($current_page, $total_pages, $base_url) {
    $pagination = '';
    
    if ($total_pages <= 1) {
        return $pagination;
    }
    
    $pagination .= '<nav aria-label="Page navigation">';
    $pagination .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">';
        $pagination .= '<i class="fas fa-chevron-left"></i> Previous</a>';
        $pagination .= '</li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a>';
        $pagination .= '</li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item">';
        $pagination .= '<a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">';
        $pagination .= 'Next <i class="fas fa-chevron-right"></i></a>';
        $pagination .= '</li>';
    }
    
    $pagination .= '</ul>';
    $pagination .= '</nav>';
    
    return $pagination;
}

/**
 * Log error message
 */
function logError($message, $context = []) {
    $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $log_message .= ' - Context: ' . json_encode($context);
    }
    error_log($log_message);
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    setFlashMessage($message, $type);
    header('Location: ' . $url);
    exit;
}
?>