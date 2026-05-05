<?php
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $CFG;

$file = $CFG->dirroot . '/mod/interactivevideo/amd/src/viewannotation.js';
$lines = file($file);

ob_start();

echo "=== playerReady event handler ===\n";
// Find where playerReady is dispatched/handled  
foreach ($lines as $i => $line) {
    if (preg_match('/playerReady|start-screen|startscreen|\.fadeOut|\.fadeIn|\.addClass.*d-none|\.removeClass.*d-none/', $line)) {
        $ln = $i + 1;
        // Show context: 3 lines before and after
        for ($j = max(0, $i-3); $j <= min(count($lines)-1, $i+3); $j++) {
            echo "L" . ($j+1) . ": " . $lines[$j];
        }
        echo "---\n";
    }
}

echo "\n\n=== Start screen click handler ===\n";
// Find start-screen click handler
$in_click = false;
foreach ($lines as $i => $line) {
    if (preg_match('/start-screen.*click|click.*start-screen/', $line)) {
        $in_click = true;
    }
    if ($in_click) {
        echo "L" . ($i+1) . ": " . $line;
        if (preg_match('/\}\)/', $line) && $in_click) {
            $in_click = false;
            echo "---\n";
        }
    }
}

echo "\n\n=== playerLoaded event handler ===\n";
foreach ($lines as $i => $line) {
    if (preg_match('/iv:playerLoaded/', $line)) {
        $ln = $i + 1;
        for ($j = max(0, $i-2); $j <= min(count($lines)-1, $i+30); $j++) {
            echo "L" . ($j+1) . ": " . $lines[$j];
        }
        echo "---\n";
    }
}

echo "\n\n=== Lines 1340-1380 (playerReady context) ===\n";
for ($j = 1339; $j < min(count($lines), 1380); $j++) {
    echo "L" . ($j+1) . ": " . $lines[$j];
}

echo "\n\n=== Lines 240-280 (init setup) ===\n";
for ($j = 239; $j < min(count($lines), 280); $j++) {
    echo "L" . ($j+1) . ": " . $lines[$j];
}

$output = ob_get_clean();
file_put_contents('/tmp/va_startscreen.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
