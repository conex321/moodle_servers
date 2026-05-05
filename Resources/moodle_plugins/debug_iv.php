<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

echo "=== DEBUGGING INTERACTIVE VIDEO (cmid=17) ===\n\n";

// 1. Check the interactivevideo record
$cm = $DB->get_record('course_modules', ['id' => 17]);
$iv = $DB->get_record('interactivevideo', ['id' => $cm->instance]);

echo "--- interactivevideo record ---\n";
echo "  id: {$iv->id}\n";
echo "  name: {$iv->name}\n";
echo "  source: '{$iv->source}'\n";
echo "  type: '{$iv->type}'\n";
echo "  videourl: '{$iv->videourl}'\n";
echo "  video: '{$iv->video}'\n";
echo "  starttime: {$iv->starttime}\n";
echo "  endtime: {$iv->endtime}\n";
echo "  completionpercentage: {$iv->completionpercentage}\n";
echo "  grade: {$iv->grade}\n";

// Display options
echo "\n--- displayoptions ---\n";
$opts = json_decode($iv->displayoptions, true);
if ($opts) {
    foreach ($opts as $k => $v) {
        echo "  $k: " . json_encode($v) . "\n";
    }
} else {
    echo "  (empty or invalid)\n";
    echo "  raw: {$iv->displayoptions}\n";
}

// 2. Check if items have valid annotations
echo "\n--- interactivevideo_items sample ---\n";
$items = $DB->get_records('interactivevideo_items', ['cmid' => 17], 'id ASC', '*', 0, 3);
foreach ($items as $item) {
    echo "  Item {$item->id}:\n";
    echo "    type: {$item->type}\n";
    echo "    annotationid: {$item->annotationid}\n";
    echo "    cmid: {$item->cmid}\n";
    echo "    courseid: {$item->courseid}\n";
    echo "    timestamp: {$item->timestamp}\n";
    echo "    title: {$item->title}\n";
    echo "    hascompletion: {$item->hascompletion}\n";
    echo "    displayoptions: {$item->displayoptions}\n";
    echo "    contextid: {$item->contextid}\n";
    echo "\n";
}

// 3. Check context
echo "--- Context check ---\n";
$context = $DB->get_record('context', ['contextlevel' => 70, 'instanceid' => 17]);
if ($context) {
    echo "  Context ID: {$context->id}\n";
    echo "  Context path: {$context->path}\n";
} else {
    echo "  ⚠ No context found for cmid 17!\n";
}

// 4. Test Vimeo URL accessibility
echo "\n--- Vimeo URL test ---\n";
$url = $iv->videourl;
echo "  URL: $url\n";
// Check if it's a valid Vimeo URL format
if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
    echo "  Video ID: {$m[1]}\n";
    echo "  Expected embed: https://player.vimeo.com/video/{$m[1]}\n";
} else {
    echo "  ⚠ URL does not match Vimeo format!\n";
}

// 5. Check if the module is visible and accessible
echo "\n--- Course module check ---\n";
echo "  cm.id: {$cm->id}\n";
echo "  cm.module: {$cm->module}\n";
echo "  cm.instance: {$cm->instance}\n";
echo "  cm.visible: {$cm->visible}\n";
echo "  cm.course: {$cm->course}\n";
echo "  cm.section: {$cm->section}\n";

// Check which section it's in
$section = $DB->get_record('course_sections', ['id' => $cm->section]);
if ($section) {
    echo "  Section: {$section->section} - {$section->name}\n";
    echo "  Sequence: {$section->sequence}\n";
}
