<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

echo "=== DEPLOYMENT VERIFICATION: Learning Activity 01 ===\n\n";

// 1. Check course modules for course 6
echo "--- Course Modules in Course 6 ---\n";
$cms = $DB->get_records_sql(
    "SELECT cm.id, m.name as modtype, cm.instance, cm.visible
     FROM {course_modules} cm
     JOIN {modules} m ON m.id = cm.module
     WHERE cm.course = 6
     ORDER BY cm.id"
);
foreach ($cms as $cm) {
    $name = '(unknown)';
    if ($cm->modtype === 'interactivevideo') {
        $rec = $DB->get_record('interactivevideo', ['id' => $cm->instance]);
        $name = $rec ? $rec->name : '?';
    } else if ($cm->modtype === 'scorm') {
        $rec = $DB->get_record('scorm', ['id' => $cm->instance]);
        $name = $rec ? $rec->name : '?';
    }
    echo "  cmid={$cm->id} | {$cm->modtype} | {$name}\n";
}

// 2. Check interactivevideo_items
echo "\n--- Interactive Video Items (cmid=17) ---\n";
$items = $DB->get_records('interactivevideo_items', ['cmid' => 17], 'timestamp ASC');
echo "  Total: " . count($items) . " items\n\n";

$quizzes = 0;
$chapters = 0;
foreach ($items as $item) {
    $mins = floor($item->timestamp / 60);
    $secs = floor($item->timestamp % 60);
    $ts = sprintf('%d:%02d', $mins, $secs);
    if ($item->type === 'richtext') {
        $quizzes++;
        echo "  [QUIZ]    @ $ts — {$item->title}\n";
    } else if ($item->type === 'chapter') {
        $chapters++;
        echo "  [CHAPTER] @ $ts — {$item->title}\n";
    } else {
        echo "  [{$item->type}] @ $ts — {$item->title}\n";
    }
}

echo "\n  Quiz questions: $quizzes\n";
echo "  Chapter markers: $chapters\n";

// 3. Verify no duplicate SCORM
echo "\n--- SCORM Duplicate Check ---\n";
$scorms = $DB->get_records_sql(
    "SELECT s.id, s.name FROM {scorm} s
     JOIN {course_modules} cm ON cm.instance = s.id
     JOIN {modules} m ON m.id = cm.module AND m.name = 'scorm'
     WHERE cm.course = 6"
);
echo "  SCORM modules in course: " . count($scorms) . "\n";
foreach ($scorms as $s) {
    echo "  - {$s->name}\n";
}
if (count($scorms) <= 1) {
    echo "  ✅ No duplicate SCORMs\n";
} else {
    echo "  ⚠ Multiple SCORMs still present\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
