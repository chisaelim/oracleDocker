<?php
// Test the actual AJAX endpoint
header('Content-Type: text/plain');

echo "Testing AJAX endpoint...\n";

// Simulate the AJAX call
$url = "http://localhost:8090/reports.php?action=sales_report&start_date=2025-01-01&end_date=2025-12-31";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = file_get_contents($url, false, $context);

echo "Response:\n";
echo $response;
echo "\n";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "JSON parsed successfully:\n";
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['summary'])) {
            echo "Summary data found:\n";
            echo "- Total Revenue: " . ($data['summary']['TOTAL_REVENUE'] ?? 'not set') . "\n";
            echo "- Total Invoices: " . ($data['summary']['TOTAL_INVOICES'] ?? 'not set') . "\n";
            echo "- Active Clients: " . ($data['summary']['ACTIVE_CLIENTS'] ?? 'not set') . "\n";
        } else {
            echo "No summary data in response\n";
        }
    } else {
        echo "Failed to parse JSON response\n";
    }
} else {
    echo "No response received\n";
}
?>