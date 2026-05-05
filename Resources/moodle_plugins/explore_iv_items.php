<?php
/**
 * Explore mod_interactivevideo item types by checking installed sub-plugins,
 * then list all annotation type classes available.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

echo "=== Available sub-plugin types ===\n";

// Check ivplugin directories
$plugin_dirs = glob($CFG->dirroot . '/mod/interactivevideo/plugins/*/');
if (empty($plugin_dirs)) {
    // Try alternative path
    $plugin_dirs = glob($CFG->dirroot . '/mod/interactivevideo/interaction/*/');
}

// Also check standard Moodle sub-plugin locations
$subplugins_file = $CFG->dirroot . '/mod/interactivevideo/db/subplugins.json';
if (file_exists($subplugins_file)) {
    echo "subplugins.json:\n";
    echo file_get_contents($subplugins_file) . "\n\n";
}

// Check for installed ivplugin types
$installed = core_plugin_manager::instance()->get_installed_plugins('ivplugin');
if ($installed) {
    echo "Installed ivplugin types:\n";
    foreach ($installed as $name => $version) {
        echo "  ivplugin_$name (v$version)\n";
    }
}

// Check interactivevideo_items for annotation type values used
echo "\n=== interactivevideo_items table (all records) ===\n";
$items = $DB->get_records('interactivevideo_items');
echo "  Total records: " . count($items) . "\n";
foreach ($items as $item) {
    echo "  ID={$item->id} type={$item->type} timestamp={$item->timestamp} title={$item->title}\n";
    echo "    content (first 200 chars): " . substr($item->content ?? '', 0, 200) . "\n";
}

// Check what types the richtext plugin uses
echo "\n=== Checking richtext plugin class ===\n";
$richtext_class = $CFG->dirroot . '/mod/interactivevideo/plugins/richtext/interactiontype.php';
if (!file_exists($richtext_class)) {
    $richtext_class = $CFG->dirroot . '/ivplugin/richtext/classes/main.php';
}
if (file_exists($richtext_class)) {
    echo "  Found: $richtext_class\n";
    // Read first 50 lines
    $lines = file($richtext_class);
    foreach (array_slice($lines, 0, 80) as $line) {
        if (stripos($line, 'class') !== false || stripos($line, 'function') !== false ||
            stripos($line, 'type') !== false || stripos($line, 'get_name') !== false) {
            echo "  " . trim($line) . "\n";
        }
    }
} else {
    echo "  Not found at expected paths\n";
    // Try to find it
    $found = shell_exec("find /var/www/html -path '*/ivplugin*' -name '*.php' -type f 2>/dev/null | head -20");
    echo "  Search results:\n$found\n";
}

echo "\n=== IV module main class ===\n";
$lib_file = $CFG->dirroot . '/mod/interactivevideo/classes/item.php';
if (!file_exists($lib_file)) {
    $lib_file = $CFG->dirroot . '/mod/interactivevideo/classes/local/item.php';
}
if (file_exists($lib_file)) {
    echo "  Found: $lib_file\n";
} else {
    echo "  Searching for item/annotation classes...\n";
    $found = shell_exec("find /var/www/html/mod/interactivevideo -name '*.php' -type f 2>/dev/null | head -30");
    echo $found . "\n";
}
