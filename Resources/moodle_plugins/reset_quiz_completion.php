<?php
/**
 * Reset ALL completion data for the interactive video module.
 * This forces students to redo all quizzes with the new interactive HTML.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

echo "=== Full Quiz Completion Reset ===\n\n";

// 1. Get the interactivevideo_completion records
$completions = $DB->get_records('interactivevideo_completion');
echo "Found " . count($completions) . " completion records\n";
foreach ($completions as $c) {
    echo "  ID={$c->id}: completeditems=" . substr($c->completeditems, 0, 100) . "\n";
    echo "    completionpercentage={$c->completionpercentage}, xp={$c->xp}\n";
}

// 2. Reset all completion records - clear completed items and XP
foreach ($completions as $c) {
    $update = new stdClass();
    $update->id = $c->id;
    $update->completeditems = '[]';
    $update->completionpercentage = 0;
    $update->xp = 0;
    $update->completiondetails = '[]';
    $update->timemodified = time();
    $DB->update_record('interactivevideo_completion', $update);
    echo "\n✅ Reset completion record #{$c->id}\n";
}

// 3. Also check if annotations have `completed` cached somewhere
$items = $DB->get_records('interactivevideo_items', ['type' => 'richtext']);
echo "\nQuiz annotations status:\n";
foreach ($items as $item) {
    echo "  #{$item->id} '{$item->title}' completiontracking='{$item->completiontracking}' ";
    echo "hascompletion=" . ($item->hascompletion ?? 'null') . "\n";
}

purge_all_caches();
echo "\n✅ All caches purged. Completion data reset.\n";
echo "   Hard-refresh (Ctrl+Shift+R) to see quizzes as 'Incomplete'.\n";
echo "   Students will now see clickable options and must click 'Complete' after answering.\n";
