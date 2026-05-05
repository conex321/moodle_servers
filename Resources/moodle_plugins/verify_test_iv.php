<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

echo "=== Verifying Interactive Video Setup ===\n\n";

// Check course
$course = $DB->get_record('course', ['shortname' => 'G1-MATH-ALG-TEST']);
if ($course) {
    echo "Course: {$course->fullname} (ID: {$course->id})\n";
} else {
    echo "ERROR: Test course not found\n";
    exit(1);
}

// Check interactivevideo instance
$ivs = $DB->get_records('interactivevideo', ['course' => $course->id]);
foreach ($ivs as $iv) {
    echo "\nInteractive Video: {$iv->name}\n";
    echo "  ID: {$iv->id}\n";
    echo "  Source: " . ($iv->source ?? 'N/A') . "\n";
    echo "  Video URL: " . ($iv->videourl ?? 'N/A') . "\n";
    echo "  Type: " . ($iv->type ?? 'N/A') . "\n";

    // Get course module
    $cm = $DB->get_record('course_modules', [
        'course' => $course->id,
        'instance' => $iv->id,
        'module' => $DB->get_field('modules', 'id', ['name' => 'interactivevideo']),
    ]);
    if ($cm) {
        echo "  Course Module ID: {$cm->id}\n";
        echo "  View URL: http://localhost:8888/mod/interactivevideo/view.php?id={$cm->id}\n";
    }
}

// Check module is registered
$mod = $DB->get_record('modules', ['name' => 'interactivevideo']);
echo "\nModule registration: " . ($mod ? "OK (ID: {$mod->id}, visible: {$mod->visible})" : "NOT FOUND") . "\n";

// Check sub-plugins
$subplugins = ['chapter', 'contentbank', 'iframe', 'richtext', 'skipsegment'];
echo "\nSub-plugins:\n";
foreach ($subplugins as $sp) {
    $installed = $DB->get_record('config_plugins', [
        'plugin' => "ivplugin_{$sp}",
        'name' => 'version',
    ]);
    echo "  ivplugin_{$sp}: " . ($installed ? "v{$installed->value}" : "NOT INSTALLED") . "\n";
}

echo "\n=== Verification Complete ===\n";
echo "Open in browser: http://localhost:8888/mod/interactivevideo/view.php?id={$cm->id}\n";
echo "Course page: http://localhost:8888/course/view.php?id={$course->id}\n";
