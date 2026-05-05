<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

ob_start();

$iv = $DB->get_record('interactivevideo', ['id' => 1]);

echo "=== IV Record #1 ===\n";
echo "videourl: " . $iv->videourl . "\n";
echo "video: " . $iv->video . "\n";
echo "source: " . $iv->source . "\n";
echo "type: " . $iv->type . "\n";
echo "starttime: " . $iv->starttime . "\n";
echo "endtime: " . $iv->endtime . "\n";
echo "\n=== Display Options (formatted) ===\n";
$opts = json_decode($iv->displayoptions, true);
echo json_encode($opts, JSON_PRETTY_PRINT) . "\n";

// Check Moodle config for interactivevideo
echo "\n=== Moodle site config for interactivevideo ===\n";
$configs2 = $DB->get_records_select('config_plugins', "plugin LIKE '%interactivevideo%'");
foreach ($configs2 as $c) {
    echo "  [{$c->plugin}] {$c->name} = " . substr($c->value, 0, 200) . "\n";
}

// Read vimeo.js player source - first 150 lines to understand URL handling
echo "\n=== vimeo.js player source (first 150 lines) ===\n";
$vimeo_js = '/var/www/html/public/mod/interactivevideo/amd/src/player/vimeo.js';
if (file_exists($vimeo_js)) {
    $lines = file($vimeo_js);
    foreach (array_slice($lines, 0, 150) as $i => $line) {
        echo ($i+1) . ": " . $line;
    }
} else {
    echo "Not found at $vimeo_js\n";
    echo shell_exec("find /var/www/html -name 'vimeo.js' -path '*/interactivevideo/*' 2>/dev/null");
}

$output = ob_get_clean();
file_put_contents('/tmp/iv_details.txt', $output);
echo "Written " . strlen($output) . " bytes to /tmp/iv_details.txt\n";
