<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

// List all tables related to interactivevideo
$tables = $DB->get_tables();
echo "=== Tables containing 'interactivevideo' or 'iv' ===\n";
foreach ($tables as $t) {
    if (strpos($t, 'interactivevideo') !== false || strpos($t, 'ivplugin') !== false) {
        echo "  $t\n";
        $cols = $DB->get_columns($t);
        foreach ($cols as $name => $col) {
            echo "    - $name ({$col->type})\n";
        }
        echo "\n";
    }
}

// Check for any annotation/item records for instance 1
echo "\n=== Checking for annotation data in interactivevideo instance 1 ===\n";
$tables_to_check = [];
foreach ($tables as $t) {
    if (strpos($t, 'interactivevideo') !== false && $t !== 'interactivevideo') {
        $tables_to_check[] = $t;
    }
}
foreach ($tables_to_check as $t) {
    $count = $DB->count_records($t);
    echo "  $t: $count records\n";
}
