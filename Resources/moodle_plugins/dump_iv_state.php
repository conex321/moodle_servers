<?php
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

$r = $DB->get_record('interactivevideo', ['id' => 1]);
$opts = json_decode($r->displayoptions, true);

echo "=== Interactive Video DB Record ===\n\n";
echo "ID: {$r->id}\n";
echo "Name: {$r->name}\n";
echo "Video URL: {$r->videourl}\n";
echo "Video: {$r->video}\n";
echo "Display as start screen: {$r->displayasstartscreen}\n";
echo "Completion %: {$r->completionpercentage}\n";
echo "Start time: {$r->startassistgrade}\n";
echo "End time: {$r->endassistgrade}\n\n";

echo "=== Display Options ===\n";
foreach ($opts as $k => $v) {
    if (is_array($v)) {
        echo "$k: " . json_encode($v) . "\n";
    } else {
        echo "$k: $v\n";
    }
}

echo "\n=== Key Settings for Playback ===\n";
echo "useoriginalvideocontrols: " . ($opts['useoriginalvideocontrols'] ?? 'NOT SET') . "\n";
echo "darkmode: " . ($opts['darkmode'] ?? 'NOT SET') . "\n";
echo "autoplay: " . ($opts['autoplay'] ?? 'NOT SET') . "\n";
echo "pauseonblur: " . ($opts['pauseonblur'] ?? 'NOT SET') . "\n";
echo "passwordprotected: " . ($opts['passwordprotected'] ?? 'NOT SET') . "\n";

echo "\n=== Module Info ===\n";
$cm = $DB->get_record('course_modules', ['id' => 17]);
if ($cm) {
    echo "Course Module ID: {$cm->id}\n";
    echo "Course: {$cm->course}\n";
    echo "Instance: {$cm->instance}\n";
    echo "Module: {$cm->module}\n";
    echo "Visible: {$cm->visible}\n";
} else {
    echo "No course_modules record for id=17!\n";
    // Try to find any IV modules
    $ivmods = $DB->get_records('course_modules', ['module' => $DB->get_field('modules', 'id', ['name' => 'interactivevideo'])]);
    echo "Found " . count($ivmods) . " IV course modules\n";
    foreach ($ivmods as $m) {
        echo "  CM {$m->id}: course={$m->course}, instance={$m->instance}, visible={$m->visible}\n";
    }
}

echo "\n=== Annotations ===\n";
$annots = $DB->get_records('interactivevideo_items', ['annotationid' => 1]);
echo "Found " . count($annots) . " annotation items\n";
foreach ($annots as $a) {
    echo "  Annotation {$a->id}: type={$a->type}, timestamp={$a->timestamp}\n";
}
