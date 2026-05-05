<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $CFG;

ob_start();

// Get the rest of vimeo.js load function (after line 290)
$file = $CFG->dirroot . '/mod/interactivevideo/amd/src/player/vimeo.js';
$lines = file($file);

echo "=== vimeo.js lines 285-400 (player creation) ===\n\n";
foreach (array_slice($lines, 284, 120) as $i => $line) {
    $ln = $i + 285;
    echo "L$ln: $line";
}

echo "\n\n=== viewannotation.js lines 1380-1500 (init calling player) ===\n\n";
$file2 = $CFG->dirroot . '/mod/interactivevideo/amd/src/viewannotation.js';
$lines2 = file($file2);
foreach (array_slice($lines2, 1379, 120) as $i => $line) {
    $ln = $i + 1380;
    echo "L$ln: $line";
}

$output = ob_get_clean();
file_put_contents('/tmp/player_create.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
