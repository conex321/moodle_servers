<?php
/**
 * Cleanup Empty Sections — Remove "Evaluations & Assessments" and "New section"
 *
 * Scans all courses and removes sections that:
 *   1. Contain "Evaluations & Assessments" in the name (with or without emoji) AND have no modules
 *   2. Are named "New section" AND have no modules
 *   3. Have empty name AND empty summary AND have no modules (truly blank)
 *
 * Usage:
 *   php cleanup_empty_sections.php           # Execute cleanup
 *   php cleanup_empty_sections.php --dry-run # Preview only, no changes
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

$dry_run = in_array('--dry-run', $argv ?? []);

if ($dry_run) {
    echo "=== DRY RUN MODE — No changes will be made ===\n\n";
} else {
    echo "=== EXECUTING CLEANUP ===\n\n";
}

// Get all courses (exclude site course id=1)
$courses = $DB->get_records_select('course', 'id > 1', null, 'id ASC');
echo "Found " . count($courses) . " courses to scan.\n\n";

$total_deleted = 0;
$courses_affected = 0;

foreach ($courses as $course) {
    // Get all sections for this course, ordered by section number DESC
    // (reverse order so deletions don't mess up numbering of sections we haven't processed yet)
    $sections = $DB->get_records('course_sections', ['course' => $course->id], 'section DESC');

    $course_deletions = [];

    foreach ($sections as $section) {
        // Never touch section 0 (Course Overview / General)
        if ($section->section == 0) {
            continue;
        }

        // Check if section has any course modules
        $has_modules = !empty(trim($section->sequence ?? ''));
        if ($has_modules) {
            continue;
        }

        $name = trim($section->name ?? '');
        $summary = trim($section->summary ?? '');
        $should_delete = false;
        $reason = '';

        // Match 1: "Evaluations & Assessments" (with or without emoji prefix)
        if (stripos($name, 'Evaluations & Assessments') !== false) {
            $should_delete = true;
            $reason = 'Evaluations & Assessments (empty)';
        }
        // Match 2: "New section"
        elseif (strcasecmp($name, 'New section') === 0) {
            $should_delete = true;
            $reason = 'New section (empty)';
        }
        // Match 3: Completely blank section (no name, no summary)
        elseif ($name === '' && ($summary === '' || $summary === '<br>')) {
            $should_delete = true;
            $reason = 'Blank section (no name, no summary)';
        }

        if ($should_delete) {
            $course_deletions[] = [
                'section_num' => $section->section,
                'name' => $name ?: '(empty)',
                'reason' => $reason,
                'section_obj' => $section,
            ];
        }
    }

    if (!empty($course_deletions)) {
        $courses_affected++;
        echo "Course: {$course->shortname} (ID: {$course->id}) — {$course->fullname}\n";

        foreach ($course_deletions as $del) {
            echo "  [DELETE] Section {$del['section_num']}: \"{$del['name']}\" — {$del['reason']}\n";

            if (!$dry_run) {
                // Use Moodle's API to cleanly delete the section
                // The third param (true) forces deletion even if section has summary
                course_delete_section($course, $del['section_obj'], true);
                $total_deleted++;
            } else {
                $total_deleted++;
            }
        }

        if (!$dry_run) {
            // Rebuild course cache after modifications
            rebuild_course_cache($course->id, true);
        }

        echo "\n";
    }
}

echo "────────────────────────────────────────\n";
echo "Courses scanned:  " . count($courses) . "\n";
echo "Courses affected: $courses_affected\n";
echo "Sections " . ($dry_run ? "to delete" : "deleted") . ": $total_deleted\n";

if ($dry_run) {
    echo "\nRe-run without --dry-run to execute.\n";
} else {
    echo "\nDone. Purge caches recommended:\n";
    echo "  php /var/www/html/public/moodleplugins/purge_caches.php\n";
}
