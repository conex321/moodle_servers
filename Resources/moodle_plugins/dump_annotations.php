<?php
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

echo "=== Interactive Video Annotations Detail ===\n\n";

// Get all annotation items for this IV
$items = $DB->get_records('interactivevideo_items', ['annotationid' => 1], 'timestamp ASC');

echo "Total items: " . count($items) . "\n\n";

foreach ($items as $item) {
    echo "--- Annotation #{$item->id} ---\n";
    echo "  Type: {$item->type}\n";
    echo "  Timestamp: {$item->timestamp}\n";
    
    // Check all fields
    $fields = get_object_vars($item);
    foreach ($fields as $key => $value) {
        if ($key == 'id' || $key == 'type' || $key == 'timestamp' || $key == 'annotationid') continue;
        if (is_null($value)) {
            echo "  $key: NULL\n";
        } elseif ($value === '') {
            echo "  $key: (empty string)\n";
        } elseif (strlen($value) > 200) {
            echo "  $key: " . substr($value, 0, 200) . "...\n";
        } else {
            echo "  $key: $value\n";
        }
    }
    echo "\n";
}

// Also check the table structure
echo "\n=== Table Schema ===\n";
$columns = $DB->get_columns('interactivevideo_items');
foreach ($columns as $col) {
    echo "  {$col->name} ({$col->type})\n";
}
