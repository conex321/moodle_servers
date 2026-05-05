<?php
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $DB;

echo "=== Interactive Video Storage Estimation ===\n\n";

// Get the IV instance for cmid=17
$sql = "SELECT iv.* FROM {interactivevideo} iv
        JOIN {course_modules} cm ON cm.instance = iv.id
        JOIN {modules} m ON m.id = cm.module AND m.name = 'interactivevideo'
        WHERE cm.id = 17";
$iv = $DB->get_record_sql($sql);

if ($iv) {
    echo "=== IV Instance ===\n";
    echo "  Name: {$iv->name}\n";
    echo "  Course: {$iv->course}\n";
    echo "  Video URL: {$iv->videourl}\n";
    echo "  Type: {$iv->type}\n";
    echo "  Start: {$iv->starttime}, End: {$iv->endtime}\n";
    $ivRowSize = strlen(json_encode($iv));
    echo "  Row size (approx): {$ivRowSize} bytes\n\n";
}

// Get annotations
$items = $DB->get_records('interactivevideo_items', ['annotationid' => $iv->id]);
echo "=== Annotations ({$iv->id}) ===\n";
echo "  Count: " . count($items) . "\n";
$totalContentSize = 0;
$totalRowSize = 0;
foreach ($items as $item) {
    $contentSize = strlen($item->content ?? '');
    $rowSize = strlen(json_encode($item));
    $totalContentSize += $contentSize;
    $totalRowSize += $rowSize;
    echo "  #{$item->id} type={$item->type}: content=" . $contentSize . " bytes, row=" . $rowSize . " bytes\n";
    echo "    title: " . substr($item->title, 0, 80) . "\n";
}
echo "\n  Total annotation content: {$totalContentSize} bytes (" . round($totalContentSize/1024, 2) . " KB)\n";
echo "  Total annotation rows: {$totalRowSize} bytes (" . round($totalRowSize/1024, 2) . " KB)\n";

// Estimate per-activity overhead
echo "\n=== Per-Activity Estimated Sizes ===\n";
$perIvRow = $ivRowSize;
$perAnnotationAvg = $totalRowSize / max(count($items), 1);
$perContentAvg = $totalContentSize / max(count($items), 1);
echo "  IV instance row: ~{$perIvRow} bytes\n";
echo "  Avg annotation row: ~" . round($perAnnotationAvg) . " bytes\n";
echo "  Avg quiz content: ~" . round($perContentAvg) . " bytes\n";

// Projected for 5 quizzes per activity
$perActivityDb = $perIvRow + (5 * $perAnnotationAvg);
echo "\n=== Per Activity (5 quizzes) DB Footprint ===\n";
echo "  Database: ~" . round($perActivityDb) . " bytes (" . round($perActivityDb/1024, 2) . " KB)\n";

// Completion overhead per student per activity
$completionRowEstimate = 200; // rough per-student per-activity
echo "  Completion tracking per student: ~{$completionRowEstimate} bytes\n";

// Scale estimate
$totalActivities = 448; // approx across all grades
$studentsPerActivity = 30;
echo "\n=== Scale Estimate ({$totalActivities} activities, {$studentsPerActivity} students) ===\n";
$totalDbSize = $totalActivities * $perActivityDb;
$totalCompletionSize = $totalActivities * $studentsPerActivity * $completionRowEstimate;
echo "  Total IV+Annotations DB: " . round($totalDbSize/1024) . " KB (" . round($totalDbSize/1024/1024, 2) . " MB)\n";
echo "  Total Completion Data: " . round($totalCompletionSize/1024) . " KB (" . round($totalCompletionSize/1024/1024, 2) . " MB)\n";
echo "  Combined: " . round(($totalDbSize + $totalCompletionSize)/1024/1024, 2) . " MB\n";
echo "\n  NOTE: Video files are hosted on Vimeo (not stored in Moodle).\n";
echo "  No local media storage required for video content.\n";
