<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

ob_start();

// List ALL columns in the interactivevideo table
echo "=== interactivevideo table columns ===\n";
$columns = $DB->get_columns('interactivevideo');
foreach ($columns as $col) {
    $default = '';
    if (isset($col->default_value)) {
        $default = " (default: " . var_export($col->default_value, true) . ")";
    }
    echo "  {$col->name}: {$col->type}{$default}\n";
}

// Get the full record with ALL fields
echo "\n=== Full IV record ===\n";
$iv = $DB->get_record('interactivevideo', ['id' => 1]);
foreach ((array)$iv as $key => $value) {
    $display = $value;
    if (strlen($display) > 200) {
        $display = substr($display, 0, 200) . '...';
    }
    echo "  $key: $display\n";
}

// Check if the page renders without PHP errors
echo "\n=== Testing view.php for PHP errors ===\n";

// Simulate what view.php does by checking key properties
$fields_used = [
    'displayoptions', 'displayasstartscreen', 'endscreentext', 'starttime', 'endtime',
    'completionpercentage', 'source', 'videourl', 'type', 'name', 'intro', 'introformat',
    'posterimage', 'extendedcompletion', 'video', 'grade'
];

foreach ($fields_used as $field) {
    if (property_exists($iv, $field)) {
        echo "  ✅ $field: exists\n";
    } else {
        echo "  ❌ $field: MISSING! This will cause a PHP error.\n";
    }
}

$output = ob_get_clean();
file_put_contents('/tmp/iv_schema_check.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
