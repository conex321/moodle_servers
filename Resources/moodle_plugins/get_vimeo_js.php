<?php
/**
 * Extract the full vimeo.js player source to understand the exact load() flow.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $CFG;

ob_start();

$file = $CFG->dirroot . '/mod/interactivevideo/amd/src/player/vimeo.js';
$lines = file($file);
$total = count($lines);

echo "=== vimeo.js full source ($total lines) ===\n\n";

// Show lines 95-250 (the load function and surrounding code)
foreach (array_slice($lines, 90, 200) as $i => $line) {
    $ln = $i + 91;
    echo "L$ln: $line";
}

$output = ob_get_clean();
file_put_contents('/tmp/vimeo_load_fn.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
