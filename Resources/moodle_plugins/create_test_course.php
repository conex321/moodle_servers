<?php
/**
 * Create a test course with Edwiser Video Format and sample URL/label activities.
 * Uses direct DB operations similar to Moodle's backup/restore and data generator.
 *
 * Run: docker exec moodle-app php /tmp/create_test_course.php
 */

define('CLI_SCRIPT', true);

require('/var/www/html/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB, $USER, $CFG;

$USER = get_admin();

cli_heading('Creating Edwiser Video Format Test Course');

// ─── 1. Verify plugins ───
$formats = core_plugin_manager::instance()->get_plugins_of_type('format');
if (!isset($formats['edwiservideoformat'])) {
    cli_error('edwiservideoformat plugin is NOT installed.');
}
cli_writeln('✓ edwiservideoformat installed.');

$mods = core_plugin_manager::instance()->get_plugins_of_type('mod');
$has_evact = isset($mods['edwiservideoactivity']);
cli_writeln($has_evact ? '✓ edwiservideoactivity installed.' : '⚠ edwiservideoactivity NOT installed.');

// ─── 2. Delete existing course if present ───
$shortname = 'EDVID-TEST-01';
$existing = $DB->get_record('course', ['shortname' => $shortname]);
if ($existing) {
    cli_writeln("Deleting existing course id={$existing->id}...");
    delete_course($existing, false);
    fix_course_sortorder();
    cli_writeln('✓ Old course deleted.');
}

// ─── 3. Create course with edwiservideoformat ───
$coursedata = new stdClass();
$coursedata->fullname   = 'Edwiser Video Format - Test Course';
$coursedata->shortname  = $shortname;
$coursedata->idnumber   = 'EDVID-TEST-01';
$coursedata->summary    = '<p>Test course for the <strong>Edwiser Video Format</strong> layout and <strong>Edwiser Video Activity</strong> module.</p>';
$coursedata->summaryformat = FORMAT_HTML;
$coursedata->format     = 'edwiservideoformat';
$coursedata->numsections = 4;
$coursedata->category   = 1;
$coursedata->visible    = 1;
$coursedata->startdate  = time();
$coursedata->enablecompletion = 1;

$course = create_course($coursedata);
cli_writeln("✓ Course created: id={$course->id}");

// ─── 4. Rename sections ───
$section_names = [
    0 => 'Welcome & Course Overview',
    1 => 'Module 1: Getting Started',
    2 => 'Module 2: Core Concepts',
    3 => 'Module 3: Advanced Topics',
    4 => 'Module 4: Final Review',
];

foreach ($section_names as $sec_num => $name) {
    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $sec_num]);
    if ($section) {
        $DB->update_record('course_sections', (object)[
            'id'            => $section->id,
            'name'          => $name,
            'summary'       => '<p>' . $name . '</p>',
            'summaryformat' => FORMAT_HTML,
        ]);
    }
}
cli_writeln('✓ Sections renamed.');

// ─── 5. Helper function to add an activity ───
function add_activity_to_course($course, $section_num, $modulename, $name, $extra_fields = []) {
    global $DB, $CFG;

    // Get the module id
    $module = $DB->get_record('modules', ['name' => $modulename], '*', MUST_EXIST);

    // Create course_modules record
    $cm = new stdClass();
    $cm->course     = $course->id;
    $cm->module     = $module->id;
    $cm->instance   = 0; // Placeholder, updated below
    $cm->section    = 0; // Updated by course_add_cm_to_section
    $cm->visible    = 1;
    $cm->visibleold = 1;
    $cm->visibleoncoursepage = 1;
    $cm->groupmode  = 0;
    $cm->groupingid = 0;
    $cm->completion = 1; // Manual completion
    $cm->added      = time();

    $cm->id = $DB->insert_record('course_modules', $cm);

    // Prepare activity instance record
    $instance = new stdClass();
    $instance->course       = $course->id;
    $instance->name         = $name;
    $instance->timemodified = time();

    // Module-specific fields
    foreach ($extra_fields as $field => $value) {
        $instance->$field = $value;
    }

    // Ensure intro fields
    if (!isset($instance->intro)) {
        $instance->intro = '';
    }
    if (!isset($instance->introformat)) {
        $instance->introformat = FORMAT_HTML;
    }

    // Insert the activity instance
    $instance->id = $DB->insert_record($modulename, $instance);

    // Update the course_modules with the instance id
    $DB->set_field('course_modules', 'instance', $instance->id, ['id' => $cm->id]);

    // Add the module to the correct section
    course_add_cm_to_section($course, $cm->id, $section_num);

    return $cm->id;
}

// ─── 6. Add activities to each section ───
$count = 0;

// Section 0: Welcome page
try {
    $cmid = add_activity_to_course($course, 0, 'page', 'Course Introduction', [
        'intro'         => '<p>Welcome to the Edwiser Video Format test course!</p>',
        'introformat'   => FORMAT_HTML,
        'content'       => '<h2>Welcome!</h2>
<p>This test course demonstrates the <strong>Edwiser Video Format</strong> for Moodle.</p>
<h3>Modules</h3>
<ul>
<li><strong>Module 1:</strong> Getting Started</li>
<li><strong>Module 2:</strong> Core Concepts</li>
<li><strong>Module 3:</strong> Advanced Topics</li>
<li><strong>Module 4:</strong> Final Review</li>
</ul>',
        'contentformat' => FORMAT_HTML,
        'display'       => 5,
        'revision'      => 1,
    ]);
    cli_writeln("  ✓ [Section 0] Page: 'Course Introduction' (cmid={$cmid})");
    $count++;
} catch (Exception $e) {
    cli_writeln("  ✗ [Section 0] Page failed: " . $e->getMessage());
}

// Video content per section (using URL module as video link containers)
$videos = [
    1 => [
        ['Welcome to the Course',      'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'Introductory video covering course objectives.'],
        ['Setting Up Your Environment', 'https://www.youtube.com/watch?v=jNQXAC9IVRw', 'Tools and setup needed for this course.'],
    ],
    2 => [
        ['Understanding the Basics',    'https://www.youtube.com/watch?v=9bZkp7q19f0', 'Deep dive into fundamental concepts.'],
        ['Applying Core Principles',    'https://www.youtube.com/watch?v=kJQP7kiw5Fk', 'Practical examples and applications.'],
        ['Common Mistakes to Avoid',    'https://www.youtube.com/watch?v=RgKAFK5djSk', 'Learn from common pitfalls.'],
    ],
    3 => [
        ['Advanced Technique #1',       'https://www.youtube.com/watch?v=OPf0YbXqDm0', 'Advanced skills for experienced users.'],
        ['Advanced Technique #2',       'https://www.youtube.com/watch?v=fJ9rUzIMcZQ', 'Real-world advanced applications.'],
    ],
    4 => [
        ['Course Summary & Takeaways',  'https://www.youtube.com/watch?v=lp-EO5I60KA', 'Review of everything covered.'],
        ['Next Steps & Resources',      'https://www.youtube.com/watch?v=hY7m5jjJ9mM', 'Continue your learning journey.'],
    ],
];

foreach ($videos as $sec_num => $activities) {
    foreach ($activities as $act) {
        list($name, $url, $desc) = $act;

        // Try Edwiser Video Activity first
        if ($has_evact) {
            try {
                // Check what columns the edwiservideoactivity table has
                $columns = $DB->get_columns('edwiservideoactivity');
                $extra = [
                    'intro'       => '<p>' . $desc . '</p>',
                    'introformat' => FORMAT_HTML,
                ];
                // Set video URL in whatever field the plugin uses
                if (isset($columns['videourl'])) {
                    $extra['videourl'] = $url;
                }
                if (isset($columns['url'])) {
                    $extra['url'] = $url;
                }
                if (isset($columns['externalurl'])) {
                    $extra['externalurl'] = $url;
                }
                if (isset($columns['videotype'])) {
                    $extra['videotype'] = 'youtube';
                }
                if (isset($columns['timecreated'])) {
                    $extra['timecreated'] = time();
                }

                $cmid = add_activity_to_course($course, $sec_num, 'edwiservideoactivity', $name, $extra);
                cli_writeln("  ✓ [Section {$sec_num}] EdwiserVideo: '{$name}' (cmid={$cmid})");
                $count++;
                continue;
            } catch (Exception $e) {
                cli_writeln("  ⚠ [Section {$sec_num}] EdwiserVideo '{$name}' failed: " . $e->getMessage());
                cli_writeln("    → Falling back to URL...");
            }
        }

        // Fallback: URL module
        try {
            $cmid = add_activity_to_course($course, $sec_num, 'url', $name, [
                'intro'       => '<p>' . $desc . '</p>',
                'introformat' => FORMAT_HTML,
                'externalurl' => $url,
                'display'     => 0,
            ]);
            cli_writeln("  ✓ [Section {$sec_num}] URL: '{$name}' (cmid={$cmid})");
            $count++;
        } catch (Exception $e) {
            cli_writeln("  ✗ [Section {$sec_num}] URL '{$name}' failed: " . $e->getMessage());
        }
    }
}

// ─── 7. Rebuild caches ───
rebuild_course_cache($course->id, true);
cli_writeln('✓ Course cache rebuilt.');

// ─── Summary ───
cli_heading('Done!');
cli_writeln("Course: '{$course->fullname}'");
cli_writeln("Short name: {$course->shortname}");
cli_writeln("Course ID: {$course->id}");
cli_writeln("Format: edwiservideoformat");
cli_writeln("Sections: 5 (0-4)");
cli_writeln("Activities added: {$count}");
cli_writeln("");
cli_writeln("Access the course at:");
cli_writeln("  {$CFG->wwwroot}/course/view.php?id={$course->id}");
cli_writeln("");
cli_writeln("Login as admin to view and configure.");
