<?php
/**
 * Fix Learning Activity 01 deployment:
 *   1. Remove duplicate SCORM (manual cleanup)
 *   2. Inject quiz questions from lesson_quiz.json into interactivevideo_items
 *   3. Inject chapter markers
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
global $CFG, $DB;

$course_id = 6;
$iv_cmid = 17;       // Interactive Video course module
$lesson_cmid = 18;   // SCORM Lesson Activity (duplicate — to remove)
$game_cmid = 19;     // SCORM Game (keep)

// ═══════════════════════════════════════════════════════════════════════
// PART 1: Remove duplicate SCORM manually
// ═══════════════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════\n";
echo "  PART 1: Remove Duplicate SCORM\n";
echo "═══════════════════════════════════════════════════\n\n";

$cm = $DB->get_record('course_modules', ['id' => $lesson_cmid]);
if ($cm) {
    $scorm = $DB->get_record('scorm', ['id' => $cm->instance]);
    echo "  Removing: {$scorm->name}\n";

    // 1. Delete SCORM tracking/sco data
    $scoes = $DB->get_records('scorm_scoes', ['scorm' => $cm->instance]);
    foreach ($scoes as $sco) {
        $DB->delete_records('scorm_scoes_data', ['scoid' => $sco->id]);
    }
    $DB->delete_records('scorm_scoes', ['scorm' => $cm->instance]);
    echo "  Cleared SCORM SCO data (" . count($scoes) . " SCOs)\n";

    // 2. Delete file storage
    try {
        $context = context_module::instance($lesson_cmid);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_scorm');
        $fs->delete_area_files($context->id);
        echo "  Cleared file storage\n";
    } catch (Exception $e) {
        echo "  File storage cleanup skipped: " . $e->getMessage() . "\n";
    }

    // 3. Remove from course_sections sequence
    $sections = $DB->get_records('course_sections', ['course' => $course_id]);
    foreach ($sections as $section) {
        if (empty($section->sequence)) continue;
        $seq = explode(',', $section->sequence);
        $new_seq = array_filter($seq, fn($id) => (int)$id !== $lesson_cmid);
        if (count($seq) !== count($new_seq)) {
            $section->sequence = implode(',', $new_seq);
            $DB->update_record('course_sections', $section);
            echo "  Removed from section {$section->section} sequence\n";
        }
    }

    // 4. Delete the SCORM record itself
    $DB->delete_records('scorm', ['id' => $cm->instance]);
    echo "  Deleted scorm instance\n";

    // 5. Delete the course_modules record
    $DB->delete_records('course_modules', ['id' => $lesson_cmid]);
    echo "  Deleted course_modules record\n";

    // 6. Delete context
    try {
        $DB->delete_records('context', ['contextlevel' => 70, 'instanceid' => $lesson_cmid]);
        echo "  Deleted context\n";
    } catch (Exception $e) {
        echo "  Context cleanup note: " . $e->getMessage() . "\n";
    }

    echo "  ✅ Removed duplicate SCORM\n\n";

    // Rename remaining game
    $game_scorm = $DB->get_record_sql(
        "SELECT s.* FROM {scorm} s JOIN {course_modules} cm ON cm.instance = s.id WHERE cm.id = ?",
        [$game_cmid]
    );
    if ($game_scorm) {
        $old = $game_scorm->name;
        $game_scorm->name = 'Grade 1 Mathematics - Algebra - Learning Activity 01 - Patterns All Around Us (Interactive Activity)';
        $game_scorm->intro = '<p>Interactive learning activity for: Patterns All Around Us (Learning Activity 01)</p>';
        $game_scorm->timemodified = time();
        $DB->update_record('scorm', $game_scorm);
        echo "  Renamed remaining SCORM:\n    Was: $old\n    Now: {$game_scorm->name}\n";
    }
} else {
    echo "  cmid $lesson_cmid not found (already removed)\n";
}

// ═══════════════════════════════════════════════════════════════════════
// PART 2: Inject quiz questions
// ═══════════════════════════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════\n";
echo "  PART 2: Inject Quiz Questions into Interactive Video\n";
echo "═══════════════════════════════════════════════════\n\n";

$quiz_file = '/data/lesson_quiz.json';
if (!file_exists($quiz_file)) {
    echo "  ❌ lesson_quiz.json not found\n";
    exit(1);
}

$quiz_data = json_decode(file_get_contents($quiz_file), true);
$questions = $quiz_data['quiz'] ?? [];
echo "  Loaded " . count($questions) . " quiz questions\n";

$iv_cm = $DB->get_record('course_modules', ['id' => $iv_cmid]);
$iv = $DB->get_record('interactivevideo', ['id' => $iv_cm->instance]);
$iv_context = context_module::instance($iv_cmid);

// Clear existing richtext items to avoid duplicates
$existing = $DB->count_records('interactivevideo_items', ['cmid' => $iv_cmid, 'type' => 'richtext']);
if ($existing > 0) {
    $DB->delete_records('interactivevideo_items', ['cmid' => $iv_cmid, 'type' => 'richtext']);
    echo "  Cleared $existing existing richtext items\n";
}

$inserted = 0;
foreach ($questions as $i => $q) {
    $qn = $i + 1;
    $ts = (float)$q['timestamp'];
    $mins = floor($ts / 60);
    $secs = floor($ts % 60);

    // Build styled HTML quiz card
    $question_html = htmlspecialchars($q['question'], ENT_QUOTES, 'UTF-8');
    $explain_html = htmlspecialchars($q['explanation'], ENT_QUOTES, 'UTF-8');
    $correct = (int)$q['correct'];

    $opts = '';
    foreach ($q['options'] as $j => $opt) {
        $letter = chr(65 + $j);
        $opt_safe = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8');
        if ($j === $correct) {
            $opts .= "<div style=\"padding:10px 14px;margin:6px 0;border-radius:8px;background:#e8f5e9;border:2px solid #4caf50;\">"
                    . "<strong>$letter)</strong> $opt_safe ✅</div>";
        } else {
            $opts .= "<div style=\"padding:10px 14px;margin:6px 0;border-radius:8px;background:#fafafa;border:1px solid #e0e0e0;\">"
                    . "<strong>$letter)</strong> $opt_safe</div>";
        }
    }

    $html = "<div style=\"max-width:560px;margin:0 auto;font-family:sans-serif;\">"
          . "<h3 style=\"color:#1565c0;\">📝 Check Your Understanding (Q$qn)</h3>"
          . "<p style=\"font-size:1.1em;font-weight:600;\">$question_html</p>"
          . $opts
          . "<div style=\"margin-top:14px;padding:10px 14px;background:#e3f2fd;border-left:4px solid #1565c0;border-radius:4px;\">"
          . "<strong>💡</strong> $explain_html</div></div>";

    $item = new stdClass();
    $item->timecreated = time();
    $item->timemodified = time();
    $item->courseid = $course_id;
    $item->cmid = $iv_cmid;
    $item->annotationid = $iv->id;
    $item->timestamp = $ts;
    $item->title = "Quiz Q$qn — " . substr($q['question'], 0, 50);
    $item->iframeurl = '';
    $item->content = $html;
    $item->xp = 10;
    $item->displayoptions = '{}';
    $item->type = 'richtext';
    $item->contentid = 0;
    $item->hascompletion = 1;
    $item->completiontracking = 'view';
    $item->advanced = '{}';
    $item->intg1 = 0;
    $item->intg2 = 0;
    $item->intg3 = 0;
    $item->char1 = '';
    $item->char2 = '';
    $item->char3 = '';
    $item->text1 = '';
    $item->text2 = '';
    $item->text3 = '';
    $item->contextid = $iv_context->id;
    $item->requiremintime = 0;

    $id = $DB->insert_record('interactivevideo_items', $item);
    $inserted++;
    echo "  ✅ Q$qn @ {$mins}:{$secs} — \"{$q['question']}\" (id: $id)\n";
}

echo "\n  Total: $inserted quiz items injected\n";

// ═══════════════════════════════════════════════════════════════════════
// PART 3: Inject chapter markers
// ═══════════════════════════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════\n";
echo "  PART 3: Inject Chapter Markers\n";
echo "═══════════════════════════════════════════════════\n\n";

$manifest_file = '/data/lesson_manifest.json';
if (!file_exists($manifest_file)) {
    echo "  ⚠ No manifest file. Skipping.\n";
} else {
    $manifest = json_decode(file_get_contents($manifest_file), true);
    $chapters = array_filter($manifest['interactions'] ?? [], fn($x) => $x['type'] === 'chapter');

    $existing_ch = $DB->count_records('interactivevideo_items', ['cmid' => $iv_cmid, 'type' => 'chapter']);
    if ($existing_ch > 0) {
        echo "  Already has $existing_ch chapters. Skipping.\n";
    } else {
        $ch = 0;
        foreach ($chapters as $c) {
            $item = new stdClass();
            $item->timecreated = time();
            $item->timemodified = time();
            $item->courseid = $course_id;
            $item->cmid = $iv_cmid;
            $item->annotationid = $iv->id;
            $item->timestamp = (float)$c['timestamp'];
            $item->title = $c['title'];
            $item->iframeurl = '';
            $item->content = '';
            $item->xp = 0;
            $item->displayoptions = '{}';
            $item->type = 'chapter';
            $item->contentid = 0;
            $item->hascompletion = 0;
            $item->completiontracking = '';
            $item->advanced = '{}';
            $item->intg1 = 0;
            $item->intg2 = 0;
            $item->intg3 = 0;
            $item->char1 = '';
            $item->char2 = '';
            $item->char3 = '';
            $item->text1 = '';
            $item->text2 = '';
            $item->text3 = '';
            $item->contextid = $iv_context->id;
            $item->requiremintime = 0;

            $DB->insert_record('interactivevideo_items', $item);
            $ch++;
        }
        echo "  ✅ Injected $ch chapter markers\n";
    }
}

rebuild_course_cache($course_id);

echo "\n═══════════════════════════════════════════════════\n";
echo "  ALL DONE\n";
echo "═══════════════════════════════════════════════════\n";
echo "  Course: {$CFG->wwwroot}/course/view.php?id=$course_id\n";
echo "  Video:  {$CFG->wwwroot}/mod/interactivevideo/view.php?id=$iv_cmid\n";
echo "  Game:   {$CFG->wwwroot}/mod/scorm/view.php?id=$game_cmid\n";
echo "═══════════════════════════════════════════════════\n";
