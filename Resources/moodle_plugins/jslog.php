<?php
/**
 * JavaScript error capture endpoint.
 * Receives browser console errors and writes them to a log file.
 * Also serves as a diagnostic injection point for view.php.
 */
require('/var/www/html/config.php');

// If this is a POST, log the error
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $log_entry = date('Y-m-d H:i:s') . " | " . ($data['type'] ?? 'unknown') . " | " . ($data['message'] ?? 'no message') . "\n";
    file_put_contents('/tmp/js_errors.log', $log_entry, FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'logged']);
    exit;
}

// If this is a GET, show the log
header('Content-Type: text/plain');
echo "=== JavaScript Error Log ===\n\n";
$log = '/tmp/js_errors.log';
if (file_exists($log)) {
    echo file_get_contents($log);
} else {
    echo "(no errors logged yet)\n";
}
echo "\n\nTo clear: DELETE " . $_SERVER['REQUEST_URI'] . "\n";

// If DELETE, clear log
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    @unlink('/tmp/js_errors.log');
    echo "Log cleared.\n";
}
