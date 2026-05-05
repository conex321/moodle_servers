<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

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

// Check the Vimeo player JS for what URL format it expects
echo "\n=== Checking vimeo.js player URL expectations ===\n";
$vimeo_js = '/var/www/html/public/mod/interactivevideo/amd/src/player/vimeo.js';
if (file_exists($vimeo_js)) {
    $content = file_get_contents($vimeo_js);
    // Find the URL/embed setup logic
    preg_match_all('/(?:url|embed|iframe|src|player\.vimeo|vimeo\.com)[^\n]{0,200}/i', $content, $matches);
    echo "Key URL-related lines from vimeo.js:\n";
    foreach (array_unique($matches[0]) as $m) {
        echo "  " . trim($m) . "\n";
    }
    
    // Check for any domain/origin checks
    preg_match_all('/(?:origin|domain|allow|referer|hostname)[^\n]{0,150}/i', $content, $matches2);
    if (!empty($matches2[0])) {
        echo "\nDomain/origin references:\n";
        foreach (array_unique($matches2[0]) as $m) {
            echo "  " . trim($m) . "\n";
        }
    }
    
    // Check what the init/constructor/setup method does
    preg_match_all('/(?:init|constructor|setup|loadVideo|getPlayer|createPlayer)[^\n]{0,200}/i', $content, $matches3);
    if (!empty($matches3[0])) {
        echo "\nInit/setup methods:\n";
        foreach (array_unique($matches3[0]) as $m) {
            echo "  " . trim($m) . "\n";
        }
    }
} else {
    echo "vimeo.js not found at $vimeo_js\n";
    // Find it
    echo shell_exec("find /var/www/html -name 'vimeo.js' -path '*/interactivevideo/*' 2>/dev/null");
}

// Also check Moodle config for any relevant settings
echo "\n=== Moodle site config for interactivevideo ===\n";
$configs = $DB->get_records_select('config', "name LIKE '%interactivevideo%'");
foreach ($configs as $c) {
    echo "  {$c->name} = {$c->value}\n";
}
$configs2 = $DB->get_records_select('config_plugins', "plugin LIKE '%interactivevideo%'");
foreach ($configs2 as $c) {
    echo "  [{$c->plugin}] {$c->name} = " . substr($c->value, 0, 200) . "\n";
}
