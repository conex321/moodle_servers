<?php
/**
 * Fix annotation displayoptions and advanced fields so quiz pop-ups actually render.
 * 
 * Root cause: The displayoptions field on each interactivevideo_items row
 * was set to '{}' (empty JSON object). The viewannotation.js code checks:
 *   if (['side', 'popup', 'inline', 'bottom'].includes(annotation.displayoptions))
 * 
 * It needs to be a STRING value like 'popup', not a JSON object.
 * 
 * The advanced field also needs clickablebeforecompleted=1 and visiblebeforecompleted=1
 * for the annotations to appear on the video navigation and be clickable.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

echo "=== Fixing Annotation Display Options ===\n\n";

// Fix richtext (quiz) annotations - set displayoptions to 'popup'
$richtext_items = $DB->get_records('interactivevideo_items', ['type' => 'richtext']);
echo "Found " . count($richtext_items) . " richtext annotations\n";

foreach ($richtext_items as $item) {
    $update = new stdClass();
    $update->id = $item->id;
    
    // Set display mode to 'popup' - this tells the JS to show a modal popup
    $update->displayoptions = 'popup';
    
    // Set advanced options so annotations are visible and clickable
    $update->advanced = json_encode([
        'visiblebeforecompleted' => '1',
        'clickablebeforecompleted' => '1', 
        'visibleaftercompleted' => '1',
        'clickableaftercompleted' => '1',
        'pausevideo' => '1',           // Pause video when popup shows
        'deletebeforecomplete' => '0',
        'deleteaftercomplete' => '0',
    ]);
    
    $DB->update_record('interactivevideo_items', $update);
    echo "  ✅ Fixed annotation #{$item->id} (richtext @ {$item->timestamp}s): displayoptions='popup'\n";
}

// Fix chapter annotations too - chapters should have 'nopause' displayoptions
$chapter_items = $DB->get_records('interactivevideo_items', ['type' => 'chapter']);
echo "\nFound " . count($chapter_items) . " chapter annotations\n";

foreach ($chapter_items as $item) {
    $update = new stdClass();
    $update->id = $item->id;
    
    // Chapters don't use displayoptions the same way, but let's ensure advanced is correct
    $update->advanced = json_encode([
        'visiblebeforecompleted' => '1',
        'clickablebeforecompleted' => '1',
        'visibleaftercompleted' => '1', 
        'clickableaftercompleted' => '1',
    ]);
    
    $DB->update_record('interactivevideo_items', $update);
}
echo "  ✅ Fixed all chapter annotations\n";

// Purge caches
purge_all_caches();
echo "\n✅ All caches purged\n";
echo "\n🎯 Reload http://localhost:8888/mod/interactivevideo/view.php?id=17 (Ctrl+Shift+R)\n";
echo "   First quiz pop-up appears at 3:54 (234 seconds)\n";
