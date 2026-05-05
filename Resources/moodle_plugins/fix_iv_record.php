<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

// Fix the interactivevideo record: source must be 'url', type must be 'vimeo'
$iv = $DB->get_record('interactivevideo', ['id' => 1]);
if (!$iv) {
    echo "ERROR: Record not found\n";
    exit(1);
}

echo "BEFORE:\n";
echo "  source = {$iv->source}\n";
echo "  type = {$iv->type}\n";
echo "  videourl = {$iv->videourl}\n";

// Fix values
$iv->source = 'url';     // Tells view.php to read from videourl field
$iv->type = 'vimeo';      // Tells the JS player to use Vimeo embed
$iv->videourl = 'https://vimeo.com/1178285195';  // Standard Vimeo URL
$iv->timemodified = time();

// Also set displayoptions to a proper JSON default if null
if (empty($iv->displayoptions)) {
    $iv->displayoptions = json_encode([
        'darkmode' => 0,
        'distractionfreemode' => 1,
        'useoriginalvideocontrols' => 0,
        'hidemainvideocontrols' => 0,
        'hideinteractions' => 0,
        'disablechapternavigation' => 0,
        'showdescriptiononheader' => 1,
        'squareposterimage' => false,
        'courseindex' => 0,
        'allowdeleteprogress' => 0,
    ]);
}

$DB->update_record('interactivevideo', $iv);

echo "\nAFTER:\n";
$iv2 = $DB->get_record('interactivevideo', ['id' => 1]);
echo "  source = {$iv2->source}\n";
echo "  type = {$iv2->type}\n";
echo "  videourl = {$iv2->videourl}\n";
echo "  displayoptions = " . substr($iv2->displayoptions, 0, 80) . "...\n";

// Rebuild cache
purge_all_caches();
echo "\nCache purged. Reload the page in your browser.\n";
echo "URL: http://localhost:8888/mod/interactivevideo/view.php?id=17\n";
