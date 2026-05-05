<?php
/**
 * Check viewannotation.js for how the Vimeo player is initialized.
 * Also check the Vimeo API for domain settings on the video.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $CFG;

ob_start();

// Read viewannotation.js - focus on init function and player loading
$file = $CFG->dirroot . '/mod/interactivevideo/amd/src/viewannotation.js';
echo "=== viewannotation.js init function ===\n";
if (file_exists($file)) {
    $lines = file($file);
    $total = count($lines);
    echo "Total lines: $total\n\n";
    
    // Show first 100 lines (init function, imports, player setup)
    echo "--- First 100 lines ---\n";
    foreach (array_slice($lines, 0, 100) as $i => $line) {
        $ln = $i + 1;
        echo "L$ln: $line";
    }
    
    // Search for where the player is loaded/initialized
    echo "\n\n--- Lines referencing player load/init ---\n";
    foreach ($lines as $i => $line) {
        if (preg_match('/(?:\.load\(|\.getInfo\(|new\s+\w+Player|playerType|player\.init|initPlayer|loadVideo|require.*player)/i', $line)) {
            $ln = $i + 1;
            // Show context: 3 lines before and after
            for ($j = max(0, $i-2); $j <= min($total-1, $i+2); $j++) {
                echo "L" . ($j+1) . ": " . $lines[$j];
            }
            echo "---\n";
        }
    }
}

$output = ob_get_clean();
file_put_contents('/tmp/iv_viewannotation.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
