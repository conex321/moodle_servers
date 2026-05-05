<?php
/**
 * Create a test course and add an Interactive Video activity with Vimeo embed.
 * Run inside Moodle container:
 *   php /tmp/create_test_iv.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
// Actually, let's find config properly
// This script is placed at /tmp/create_test_iv.php

global $CFG, $DB;

// 1. Find or create the test course
$coursename = 'Grade 1 - Mathematics - Algebra (Test)';
$shortname = 'G1-MATH-ALG-TEST';

$course = $DB->get_record('course', ['shortname' => $shortname]);
if (!$course) {
    require_once($CFG->dirroot . '/course/lib.php');
    
    // Get the default category
    $category = $DB->get_record('course_categories', ['id' => 1]);
    if (!$category) {
        $category = core_course_category::get_default();
    }
    
    $coursedata = (object)[
        'fullname' => $coursename,
        'shortname' => $shortname,
        'category' => $category ? $category->id : 1,
        'summary' => 'Test course for validating the Interactive Video pipeline (Phase 5+6). Grade 1 Mathematics - Algebra.',
        'format' => 'topics',
        'numsections' => 5,
        'visible' => 1,
    ];
    
    $course = create_course($coursedata);
    echo "Created course: {$course->fullname} (ID: {$course->id})\n";
} else {
    echo "Course already exists: {$course->fullname} (ID: {$course->id})\n";
}

// 2. Add Interactive Video activity
// Check if mod_interactivevideo is available
$module = $DB->get_record('modules', ['name' => 'interactivevideo']);
if (!$module) {
    echo "ERROR: mod_interactivevideo not found in modules table\n";
    exit(1);
}
echo "Module found: interactivevideo (ID: {$module->id})\n";

// Check if we already added this activity
$existing = $DB->get_record_sql(
    "SELECT cm.id, iv.name 
     FROM {course_modules} cm 
     JOIN {interactivevideo} iv ON iv.id = cm.instance 
     WHERE cm.course = ? AND iv.name = ?",
    [$course->id, 'Patterns All Around Us']
);

if ($existing) {
    echo "Activity already exists: {$existing->name} (cmid: {$existing->id})\n";
    echo "URL: {$CFG->wwwroot}/mod/interactivevideo/view.php?id={$existing->id}\n";
    exit(0);
}

// 3. Create the Interactive Video instance
require_once($CFG->dirroot . '/course/modlib.php');

// Vimeo video data from vimeo_result.json
$vimeo_id = '1178285195';
$vimeo_url = "https://vimeo.com/{$vimeo_id}";

// Build the module info
$moduleinfo = (object)[
    'modulename' => 'interactivevideo',
    'module' => $module->id,
    'course' => $course->id,
    'section' => 1,
    'visible' => 1,
    'name' => 'Patterns All Around Us',
    'intro' => '<p>Interactive video lesson: Patterns All Around Us (Grade 1 Mathematics - Algebra, Activity 01)</p><p>This lesson covers pattern recognition, attributes, pattern cores, extending patterns, and creating your own patterns.</p>',
    'introformat' => FORMAT_HTML,
    'source' => 'url',  // Must be 'url' for mod_interactivevideo to read videourl field
    'videourl' => $vimeo_url,
    'completionview' => 1,
];

// Try to add the module using Moodle API
try {
    // Get course module info defaults
    $course_module = (object)[
        'course' => $course->id,
        'module' => $module->id,
        'instance' => 0,
        'section' => 1,
        'visible' => 1,
        'added' => time(),
    ];
    
    // Insert the interactivevideo record directly
    $iv_record = (object)[
        'course' => $course->id,
        'name' => 'Patterns All Around Us',
        'intro' => '<p>Interactive video lesson: Patterns All Around Us (Grade 1 Mathematics - Algebra, Activity 01)</p>',
        'introformat' => FORMAT_HTML,
        'source' => 'url',
        'videourl' => $vimeo_url,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    
    // Check the table columns first
    $columns = $DB->get_columns('interactivevideo');
    echo "interactivevideo table columns: " . implode(', ', array_keys($columns)) . "\n";
    
    // Only include valid columns
    $valid_record = new stdClass();
    $valid_record->course = $course->id;
    $valid_record->name = 'Patterns All Around Us';
    $valid_record->intro = '<p>Interactive video lesson covering pattern recognition for Grade 1.</p>';
    $valid_record->introformat = FORMAT_HTML;
    $valid_record->timecreated = time();
    $valid_record->timemodified = time();
    
    // Add optional fields if they exist in the table
    if (isset($columns['source'])) {
        $valid_record->source = 'url';
    }
    if (isset($columns['videourl'])) {
        $valid_record->videourl = $vimeo_url;
    }
    if (isset($columns['type'])) {
        $valid_record->type = 'vimeo';
    }
    
    $instance_id = $DB->insert_record('interactivevideo', $valid_record);
    echo "Created interactivevideo instance: ID {$instance_id}\n";
    
    // Create the course module
    $cm_record = (object)[
        'course' => $course->id,
        'module' => $module->id,
        'instance' => $instance_id,
        'section' => 0,
        'visible' => 1,
        'added' => time(),
    ];
    
    $cmid = $DB->insert_record('course_modules', $cm_record);
    echo "Created course_module: cmid {$cmid}\n";
    
    // Add to course section
    require_once($CFG->dirroot . '/course/lib.php');
    course_add_cm_to_section($course, $cmid, 1);
    echo "Added to section 1\n";
    
    // Rebuild course cache
    rebuild_course_cache($course->id);
    
    echo "\n=== SUCCESS ===\n";
    echo "Activity URL: {$CFG->wwwroot}/mod/interactivevideo/view.php?id={$cmid}\n";
    echo "Course URL: {$CFG->wwwroot}/course/view.php?id={$course->id}\n";
    echo "Vimeo ID: {$vimeo_id}\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
