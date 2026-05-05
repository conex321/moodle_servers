<?php
/**
 * Fix quiz completion tracking from 'view' to 'manual'.
 * 
 * 'view' = auto-complete when content is rendered (student can't interact)
 * 'manual' = student must click "Complete" button after interacting
 */
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

echo "=== Fixing Quiz Completion Tracking ===\n\n";

$items = $DB->get_records('interactivevideo_items', ['type' => 'richtext']);
echo "Found " . count($items) . " richtext (quiz) annotations\n\n";

foreach ($items as $item) {
    echo "Annotation #{$item->id} @ {$item->timestamp}s: completiontracking was '{$item->completiontracking}'";
    
    $update = new stdClass();
    $update->id = $item->id;
    $update->completiontracking = 'manual';  // Student must click "Complete"
    
    // Also reset any existing completion data so users can re-do it
    $DB->update_record('interactivevideo_items', $update);
    echo " → now 'manual' ✅\n";
}

// Delete any existing completion records for these items so they can be redone
$item_ids = array_keys($items);
if (!empty($item_ids)) {
    list($insql, $params) = $DB->get_in_or_equal($item_ids);
    $deleted = $DB->delete_records_select('interactivevideo_completion', "cmid $insql", $params);
    echo "\nCleared $deleted existing completion records\n";
}

purge_all_caches();
echo "\n✅ Done! Quizzes now require manual completion.\n";
echo "   Students must click the 'Complete' button after answering.\n";
