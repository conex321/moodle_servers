<?php
/**
 * Check how the interactivevideo plugin renders the player in the view page.
 * Look at the renderer/output to understand what gets passed to JavaScript.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;

ob_start();

// 1. Check launch.js for how it reads IV data
echo "=== launch.js analysis (how video URL is passed to player) ===\n";
$launch_js = $CFG->dirroot . '/mod/interactivevideo/amd/src/launch.js';
if (file_exists($launch_js)) {
    $content = file_get_contents($launch_js);
    
    // Find where it reads the videourl/video/source fields
    preg_match_all('/(?:videourl|video_url|videoUrl|\.video\b|\.source\b|\.type\b)[^\n]{0,200}/i', $content, $matches);
    echo "References to video fields:\n";
    foreach (array_unique($matches[0]) as $m) {
        echo "  " . trim($m) . "\n";
    }
    
    // Find the player initialization
    preg_match_all('/(?:loadPlayer|initPlayer|new.*Player|require.*player)[^\n]{0,200}/i', $content, $matches2);
    echo "\nPlayer initialization:\n";
    foreach (array_unique($matches2[0]) as $m) {
        echo "  " . trim($m) . "\n";
    }
    
    // Find where AMD data is read from the page
    preg_match_all('/(?:data-|dataset\.|getAttribute|M\.cfg|init\b.*function|getElement)[^\n]{0,200}/i', $content, $matches3);
    echo "\nData reading patterns:\n";
    $seen = [];
    foreach (array_unique($matches3[0]) as $m) {
        $trimmed = trim($m);
        if (!in_array($trimmed, $seen)) {
            echo "  $trimmed\n";
            $seen[] = $trimmed;
        }
    }
    
    echo "\n--- Lines containing 'url' (context for player URL source) ---\n";
    $lines = file($launch_js);
    foreach ($lines as $i => $line) {
        if (stripos($line, 'url') !== false && stripos($line, '//') !== 0) {
            $ln = $i + 1;
            echo "  L$ln: " . trim($line) . "\n";
        }
    }
} else {
    echo "launch.js not found at $launch_js\n";
}

// 2. Check the view.php for how data is passed to JS
echo "\n=== view.php analysis ===\n";
$view_php = $CFG->dirroot . '/mod/interactivevideo/view.php';
if (file_exists($view_php)) {
    $content = file_get_contents($view_php);
    // Find js_call_amd or page->requires->js_init_call
    preg_match_all('/(?:js_call_amd|js_init|PAGE.*js|data_for_js|renderer|render|output)[^\n]{0,200}/i', $content, $matches);
    echo "JS initialization:\n";
    foreach (array_unique($matches[0]) as $m) {
        echo "  " . trim($m) . "\n";
    }
    
    // Just show all of view.php if small enough
    echo "\n--- Full view.php ---\n";
    echo $content;
}

// 3. Check the renderer or output class
echo "\n=== Renderer/output check ===\n";
$output_dir = $CFG->dirroot . '/mod/interactivevideo/classes/output/';
if (is_dir($output_dir)) {
    $files = scandir($output_dir);
    echo "Output classes:\n";
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') echo "  $f\n";
    }
}

$renderer = $CFG->dirroot . '/mod/interactivevideo/renderer.php';
if (file_exists($renderer)) {
    echo "\nrenderer.php exists (" . filesize($renderer) . " bytes)\n";
}

$output = ob_get_clean();
file_put_contents('/tmp/iv_launch_analysis.txt', $output);
echo "Written " . strlen($output) . " bytes to /tmp/iv_launch_analysis.txt\n";
