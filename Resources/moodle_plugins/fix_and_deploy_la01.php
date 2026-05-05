<?php
/**
 * Fix existing test deployment:
 *   1. Rename the Interactive Video to proper nomenclature
 *   2. Add SCORM Lesson Activity
 *   3. Add SCORM Game
 * Uses Learning_Activity_01 (Patterns All Around Us) from mounted data.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/filelib.php');
global $CFG, $DB;

$course_id = 6;  // The test course we created
$section_num = 1;

// ─── 1. Rename existing Interactive Video ───────────────────────────
echo "─── Step 1: Rename Interactive Video ────────────\n";
$iv = $DB->get_record('interactivevideo', ['id' => 1]);
if ($iv) {
    $old_name = $iv->name;
    $iv->name = 'Grade 1 Mathematics - Algebra - Learning Activity 01 - Patterns All Around Us';
    $iv->intro = '<p>Interactive video lesson: Patterns All Around Us (Grade 1 Mathematics - Algebra, Learning Activity 01)</p>';
    $iv->timemodified = time();
    $DB->update_record('interactivevideo', $iv);
    echo "  Renamed: '$old_name' → '{$iv->name}'\n";
} else {
    echo "  ⚠ Interactive Video record not found\n";
}

// ─── 2. Update course name ──────────────────────────────────────────
echo "\n─── Step 2: Update Course ────────────────────────\n";
$course = $DB->get_record('course', ['id' => $course_id]);
if ($course && $course->shortname !== 'G1-MATH-ALG') {
    $course->fullname = 'Grade 1 - Mathematics - Algebra';
    $course->shortname = 'G1-MATH-ALG';
    $DB->update_record('course', $course);
    echo "  Course renamed to: {$course->fullname}\n";
} else {
    echo "  Course: {$course->fullname}\n";
}

// ─── Helper: upload file ────────────────────────────────────────────
function upload_scorm_file($contextid, $filepath, $filename) {
    $fs = get_file_storage();
    $existing = $fs->get_file($contextid, 'mod_scorm', 'package', 0, '/', $filename);
    if ($existing) {
        $existing->delete();
    }
    return $fs->create_file_from_pathname([
        'contextid' => $contextid,
        'component' => 'mod_scorm',
        'filearea'  => 'package',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename,
    ], $filepath);
}

// ─── 3. Deploy SCORM Lesson Activity ────────────────────────────────
echo "\n─── Step 3: SCORM Lesson Activity ─────────────────\n";

$scorm_zip = '/data/lesson_activity.zip';
$scorm_name = 'Grade 1 Mathematics - Algebra - Learning Activity 01 - Patterns All Around Us (Lesson Activity)';

$module_scorm = $DB->get_record('modules', ['name' => 'scorm']);
if (!$module_scorm) {
    echo "  ❌ mod_scorm not found in Moodle\n";
} else if (!file_exists($scorm_zip)) {
    echo "  ❌ lesson_activity.zip not found at $scorm_zip\n";
} else {
    // Check if already exists
    $existing = $DB->get_record_sql(
        "SELECT cm.id FROM {course_modules} cm JOIN {scorm} s ON s.id = cm.instance WHERE cm.course = ? AND s.name = ?",
        [$course_id, $scorm_name]
    );
    if ($existing) {
        echo "  Already exists (cmid: {$existing->id})\n";
    } else {
        $scorm_record = new stdClass();
        $scorm_record->course = $course_id;
        $scorm_record->name = $scorm_name;
        $scorm_record->intro = '<p>Interactive lesson activity for: Patterns All Around Us (Learning Activity 01)</p>';
        $scorm_record->introformat = FORMAT_HTML;
        $scorm_record->scormtype = 'local';
        $scorm_record->reference = 'lesson_activity.zip';
        $scorm_record->version = 'SCORM_1.2';
        $scorm_record->maxgrade = 100;
        $scorm_record->grademethod = 1;
        $scorm_record->whatgrade = 0;
        $scorm_record->maxattempt = 0;
        $scorm_record->forcecompleted = 0;
        $scorm_record->forcenewattempt = 0;
        $scorm_record->lastattemptlock = 0;
        $scorm_record->masteryoverride = 1;
        $scorm_record->displayattemptstatus = 1;
        $scorm_record->displaycoursestructure = 0;
        $scorm_record->updatefreq = 0;
        $scorm_record->md5hash = '';
        $scorm_record->revision = 0;
        $scorm_record->launch = 0;
        $scorm_record->skipview = 2;
        $scorm_record->hidebrowse = 0;
        $scorm_record->hidetoc = 0;
        $scorm_record->nav = 1;
        $scorm_record->auto = 0;
        $scorm_record->popup = 0;
        $scorm_record->options = '';
        $scorm_record->width = 100;
        $scorm_record->height = 500;
        $scorm_record->timeopen = 0;
        $scorm_record->timeclose = 0;
        $scorm_record->autocommit = 1;
        $scorm_record->timemodified = time();

        $instance_id = $DB->insert_record('scorm', $scorm_record);
        echo "  Created scorm instance: $instance_id\n";

        // Create course module
        $cm = (object)[
            'course' => $course_id,
            'module' => $module_scorm->id,
            'instance' => $instance_id,
            'section' => 0,
            'visible' => 1,
            'added' => time(),
        ];
        $cmid = $DB->insert_record('course_modules', $cm);
        course_add_cm_to_section($course, $cmid, $section_num);

        // Upload ZIP to file storage
        $cm_context = context_module::instance($cmid);
        $file = upload_scorm_file($cm_context->id, $scorm_zip, 'lesson_activity.zip');

        if ($file) {
            // Parse SCORM package
            require_once($CFG->dirroot . '/mod/scorm/locallib.php');
            $scorm_record->id = $instance_id;
            scorm_parse($scorm_record, true);
            echo "  ✅ Deployed: $scorm_name\n";
            echo "     cmid: $cmid\n";
            echo "     URL: {$CFG->wwwroot}/mod/scorm/view.php?id=$cmid\n";
        } else {
            echo "  ❌ Failed to upload file\n";
        }
    }
}

// ─── 4. Deploy SCORM Game ───────────────────────────────────────────
echo "\n─── Step 4: SCORM Game ────────────────────────────\n";

$scorm_game_dir = '/data/scorm_game';
$game_name = 'Grade 1 Mathematics - Algebra - Learning Activity 01 - Patterns All Around Us (Game)';

if (!$module_scorm) {
    echo "  ❌ mod_scorm not found\n";
} else if (!is_dir($scorm_game_dir) || !file_exists($scorm_game_dir . '/imsmanifest.xml')) {
    echo "  ❌ scorm_game/ not found at $scorm_game_dir\n";
} else {
    $existing = $DB->get_record_sql(
        "SELECT cm.id FROM {course_modules} cm JOIN {scorm} s ON s.id = cm.instance WHERE cm.course = ? AND s.name = ?",
        [$course_id, $game_name]
    );
    if ($existing) {
        echo "  Already exists (cmid: {$existing->id})\n";
    } else {
        // ZIP the scorm_game directory
        $game_zip = '/tmp/scorm_game_deploy.zip';
        $zip = new ZipArchive();
        if ($zip->open($game_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($scorm_game_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $f) {
                $rel = str_replace($scorm_game_dir . '/', '', $f->getRealPath());
                $rel = str_replace($scorm_game_dir . '\\', '', $rel);
                $zip->addFile($f->getRealPath(), $rel);
            }
            $zip->close();
            echo "  Zipped scorm_game/ → " . round(filesize($game_zip) / 1024) . " KB\n";
        } else {
            echo "  ❌ Failed to create ZIP\n";
            $game_zip = null;
        }

        if ($game_zip && file_exists($game_zip)) {
            $game_record = new stdClass();
            $game_record->course = $course_id;
            $game_record->name = $game_name;
            $game_record->intro = '<p>Interactive learning game for: Patterns All Around Us (Learning Activity 01)</p>';
            $game_record->introformat = FORMAT_HTML;
            $game_record->scormtype = 'local';
            $game_record->reference = 'scorm_game.zip';
            $game_record->version = 'SCORM_1.2';
            $game_record->maxgrade = 100;
            $game_record->grademethod = 1;
            $game_record->whatgrade = 0;
            $game_record->maxattempt = 0;
            $game_record->forcecompleted = 0;
            $game_record->forcenewattempt = 0;
            $game_record->lastattemptlock = 0;
            $game_record->masteryoverride = 1;
            $game_record->displayattemptstatus = 1;
            $game_record->displaycoursestructure = 0;
            $game_record->updatefreq = 0;
            $game_record->md5hash = '';
            $game_record->revision = 0;
            $game_record->launch = 0;
            $game_record->skipview = 2;
            $game_record->hidebrowse = 0;
            $game_record->hidetoc = 0;
            $game_record->nav = 1;
            $game_record->auto = 0;
            $game_record->popup = 0;
            $game_record->options = '';
            $game_record->width = 100;
            $game_record->height = 600;
            $game_record->timeopen = 0;
            $game_record->timeclose = 0;
            $game_record->autocommit = 1;
            $game_record->timemodified = time();

            $game_instance = $DB->insert_record('scorm', $game_record);
            $cm = (object)[
                'course' => $course_id,
                'module' => $module_scorm->id,
                'instance' => $game_instance,
                'section' => 0,
                'visible' => 1,
                'added' => time(),
            ];
            $game_cmid = $DB->insert_record('course_modules', $cm);
            course_add_cm_to_section($course, $game_cmid, $section_num);

            $cm_context = context_module::instance($game_cmid);
            $file = upload_scorm_file($cm_context->id, $game_zip, 'scorm_game.zip');

            if ($file) {
                require_once($CFG->dirroot . '/mod/scorm/locallib.php');
                $game_record->id = $game_instance;
                scorm_parse($game_record, true);
                echo "  ✅ Deployed: $game_name\n";
                echo "     cmid: $game_cmid\n";
                echo "     URL: {$CFG->wwwroot}/mod/scorm/view.php?id=$game_cmid\n";
            } else {
                echo "  ❌ Failed to upload game package\n";
            }
            @unlink($game_zip);
        }
    }
}

// ─── 5. Rename section ──────────────────────────────────────────────
echo "\n─── Step 5: Section Label ─────────────────────────\n";
$section = $DB->get_record('course_sections', ['course' => $course_id, 'section' => $section_num]);
if ($section) {
    $section->name = 'Learning Activity 01 - Patterns All Around Us';
    $section->summary = '<p>Video lesson, interactive activity, and learning game for Patterns All Around Us.</p>';
    $section->summaryformat = FORMAT_HTML;
    $DB->update_record('course_sections', $section);
    echo "  Section renamed: {$section->name}\n";
}

// Rebuild cache
rebuild_course_cache($course_id);

echo "\n═══════════════════════════════════════════════════\n";
echo "  DEPLOYMENT COMPLETE\n";
echo "  Course: {$CFG->wwwroot}/course/view.php?id=$course_id\n";
echo "═══════════════════════════════════════════════════\n";
