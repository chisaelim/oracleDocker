<?php
// Utility functions for the application
class Utils {
    
    /**
     * Sanitize input data
     * @param string $data
     * @return string
     */
    public static function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    /**
     * Format currency
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public static function formatCurrency($amount, $currency = '$') {
        return $currency . number_format($amount, 2);
    }
    
    /**
     * Format date
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        if (!$date) return '';
        return date($format, strtotime($date));
    }
    
    /**
     * Generate pagination
     * @param int $currentPage
     * @param int $totalPages
     * @param string $baseUrl
     * @return string
     */
    public static function generatePagination($currentPage, $totalPages, $baseUrl) {
        if ($totalPages <= 1) return '';
        
        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        
        // Previous button
        if ($currentPage > 1) {
            $prevPage = $currentPage - 1;
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $prevPage . '">Previous</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
            }
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
        }
        
        // Next button
        if ($currentPage < $totalPages) {
            $nextPage = $currentPage + 1;
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $nextPage . '">Next</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        }
        
        $html .= '</ul></nav>';
        return $html;
    }
    
    /**
     * Set success message
     * @param string $message
     */
    public static function setSuccessMessage($message) {
        $_SESSION['success_message'] = $message;
    }
    
    /**
     * Set error message
     * @param string $message
     */
    public static function setErrorMessage($message) {
        $_SESSION['error_message'] = $message;
    }
    
    /**
     * Redirect to a page
     * @param string $url
     */
    public static function redirect($url) {
        header("Location: $url");
        exit();
    }
    
    /**
     * Generate a simple table from array data
     * @param array $data
     * @param array $headers
     * @param array $actions
     * @return string
     */
    public static function generateTable($data, $headers, $actions = []) {
        if (empty($data)) {
            return '<div class="alert alert-info">No data found.</div>';
        }
        
        $html = '<div class="table-responsive"><table class="table table-striped table-hover">';
        
        // Headers
        $html .= '<thead class="table-dark"><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        if (!empty($actions)) {
            $html .= '<th>Actions</th>';
        }
        $html .= '</tr></thead>';
        
        // Body
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($headers as $key => $header) {
                $value = $row[strtoupper($key)] ?? $row[$key] ?? '';
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            
            // Actions
            if (!empty($actions)) {
                $html .= '<td>';
                foreach ($actions as $action) {
                    $url = str_replace('{id}', $row['ID'] ?? $row[array_keys($row)[0]], $action['url']);
                    $class = $action['class'] ?? 'btn btn-sm btn-outline-primary';
                    $html .= '<a href="' . $url . '" class="' . $class . ' me-1">' . $action['label'] . '</a>';
                }
                $html .= '</td>';
            }
            
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        
        return $html;
    }
}
?>