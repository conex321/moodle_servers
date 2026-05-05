<?php
/**
 * Inspect the actual quiz HTML stored in the database.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

$items = $DB->get_records('interactivevideo_items', ['type' => 'richtext']);
foreach ($items as $item) {
    echo "=== Annotation #{$item->id}: {$item->title} ===\n";
    echo "completiontracking: {$item->completiontracking}\n";
    echo "displayoptions: {$item->displayoptions}\n";
    echo "hascompletion: {$item->hascompletion}\n";
    echo "advanced: " . substr($item->advanced, 0, 200) . "\n";
    
    // The content is the raw HTML
    echo "\n--- Content (first 1500 chars) ---\n";
    echo substr($item->content, 0, 1500) . "\n";
    echo "\n--- Content has <details>? " . (strpos($item->content, '<details') !== false ? 'YES' : 'NO') . " ---\n";
    echo "--- Content has <summary>? " . (strpos($item->content, '<summary') !== false ? 'YES' : 'NO') . " ---\n";
    echo "--- Content has <input? " . (strpos($item->content, '<input') !== false ? 'YES' : 'NO') . " ---\n";
    echo "--- Content has type=\"radio\"? " . (strpos($item->content, 'type="radio"') !== false ? 'YES' : 'NO') . " ---\n";
    echo "\n" . str_repeat('=', 80) . "\n\n";
}
