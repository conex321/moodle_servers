<?php
/**
 * Moodle Front Page Configuration — Grade 1-8 Virtual Academy
 *
 * Programmatically sets all RemUI theme front page settings to create
 * a professional, branded home page for the virtual school.
 *
 * Usage (inside Moodle container):
 *   php /tmp/configure_frontpage.php [--images-dir=/tmp/frontpage_images]
 *
 * Prerequisites:
 *   - Images copied to container at /tmp/frontpage_images/
 *   - Required images: hero_banner.png, school_logo.png
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

global $CFG, $DB, $USER;

// Parse --images-dir argument
$images_dir = '/tmp/frontpage_images';
foreach ($argv as $arg) {
    if (strpos($arg, '--images-dir=') === 0) {
        $images_dir = substr($arg, strlen('--images-dir='));
    }
}

echo "═══════════════════════════════════════════════════\n";
echo "  MOODLE FRONT PAGE CONFIGURATION\n";
echo "  Grade 1-8 Virtual Academy\n";
echo "═══════════════════════════════════════════════════\n\n";

// ─── Helper: Set RemUI config ───────────────────────────────────────
function set_remui($name, $value) {
    set_config($name, $value, 'theme_remui');
    $display = strlen($value) > 80 ? substr($value, 0, 77) . '...' : $value;
    echo "  ✅ $name = $display\n";
}

// ─── Helper: Upload file to theme settings ──────────────────────────
function upload_theme_file($setting_name, $filepath, $filename) {
    global $CFG;
    $fs = get_file_storage();
    $syscontext = context_system::instance();

    // Delete existing file
    $existing = $fs->get_area_files($syscontext->id, 'theme_remui', $setting_name, 0, 'id', false);
    foreach ($existing as $file) {
        $file->delete();
    }

    if (!file_exists($filepath)) {
        echo "  ⚠ File not found: $filepath\n";
        return false;
    }

    $file = $fs->create_file_from_pathname([
        'contextid' => $syscontext->id,
        'component' => 'theme_remui',
        'filearea'  => $setting_name,
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename,
    ], $filepath);

    // Set the config to the draft filename so theme can find it
    set_config($setting_name, '/' . $filename, 'theme_remui');
    echo "  ✅ Uploaded: $filename → $setting_name\n";
    return true;
}


// ═══════════════════════════════════════════════════════════════════
// STEP 1: SITE IDENTITY
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 1: Site Identity ─────────────────────────\n";

$site = $DB->get_record('course', ['id' => 1]);
$site->fullname = 'Grade 1-8 Virtual Academy';
$site->shortname = 'G18VA';
$site->summary = '<p>Welcome to the <strong>Grade 1-8 Virtual Academy</strong> — Ontario\'s premier self-paced online learning platform for elementary and middle school students. Our curriculum is fully aligned with the Ontario Ministry of Education standards, featuring interactive video lessons, hands-on learning games, and comprehensive assessments designed to make learning engaging and effective.</p>';
$site->summaryformat = FORMAT_HTML;
$DB->update_record('course', $site);
echo "  ✅ Site name: {$site->fullname}\n";
echo "  ✅ Site shortname: {$site->shortname}\n";
echo "  ✅ Site summary updated\n";

// Core site settings
set_config('fullname', $site->fullname);

// Logo and favicon
set_remui('logoorsitename', 'iconsitename');
set_remui('sitenamecolor', '#1a237e');

// Upload logo if available
$logo_path = $images_dir . '/school_logo.png';
if (file_exists($logo_path)) {
    upload_theme_file('logo', $logo_path, 'school_logo.png');
    upload_theme_file('logomini', $logo_path, 'school_logo_mini.png');
    upload_theme_file('faviconurl', $logo_path, 'favicon.png');
    set_remui('logoorsitename', 'logo');
}


// ═══════════════════════════════════════════════════════════════════
// STEP 2: FRONT PAGE CHOOSER & LAYOUT
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 2: Front Page Layout ─────────────────────\n";

// frontpagechooser: 0 = static sections, 1 = slider
set_remui('frontpagechooser', '1');
set_remui('frontpageimagecontent', '1');
set_remui('homepagetransparentheader', '1');

// Set Moodle core frontpage to show course list
set_config('frontpage', '6');  // 6 = course list
set_config('frontpageloggedin', '6');
set_config('defaulthomepage', '0');  // 0 = site home
echo "  ✅ Front page set to show course list\n";
echo "  ✅ Default home page = Site Home\n";


// ═══════════════════════════════════════════════════════════════════
// STEP 3: HERO SLIDER
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 3: Hero Slider ───────────────────────────\n";

set_remui('slidercount', '3');
set_remui('sliderautoplay', '1');
set_remui('slideinterval', '5000');

// Slide 1: Main Welcome
$hero_path = $images_dir . '/hero_banner.png';
if (file_exists($hero_path)) {
    upload_theme_file('slideimage1', $hero_path, 'hero_banner.png');
}
set_remui('slidertext1', '<div style="text-align:center;"><h1 style="font-size:2.8em;font-weight:800;color:#ffffff;text-shadow:2px 2px 8px rgba(0,0,0,0.6);margin-bottom:12px;">Welcome to Grade 1-8 Virtual Academy</h1><p style="font-size:1.3em;color:#e0e0e0;text-shadow:1px 1px 4px rgba(0,0,0,0.5);max-width:700px;margin:0 auto;">Ontario curriculum-aligned self-paced learning for Grades 1 through 8. Interactive lessons, hands-on games, and expert-guided assessments.</p></div>');
set_remui('sliderbuttontext1', 'Browse Courses');
set_remui('sliderurl1', '/course/');

// Slide 2: Interactive Learning
if (file_exists($hero_path)) {
    upload_theme_file('slideimage2', $hero_path, 'hero_banner_2.png');
}
set_remui('slidertext2', '<div style="text-align:center;"><h1 style="font-size:2.8em;font-weight:800;color:#ffffff;text-shadow:2px 2px 8px rgba(0,0,0,0.6);margin-bottom:12px;">Interactive Video Lessons</h1><p style="font-size:1.3em;color:#e0e0e0;text-shadow:1px 1px 4px rgba(0,0,0,0.5);max-width:700px;margin:0 auto;">Every lesson features narrated video with embedded quiz questions, chapter navigation, and captions — making learning engaging and accessible.</p></div>');
set_remui('sliderbuttontext2', 'Start Learning');
set_remui('sliderurl2', '/course/');

// Slide 3: Games
if (file_exists($hero_path)) {
    upload_theme_file('slideimage3', $hero_path, 'hero_banner_3.png');
}
set_remui('slidertext3', '<div style="text-align:center;"><h1 style="font-size:2.8em;font-weight:800;color:#ffffff;text-shadow:2px 2px 8px rgba(0,0,0,0.6);margin-bottom:12px;">Learn Through Play</h1><p style="font-size:1.3em;color:#e0e0e0;text-shadow:1px 1px 4px rgba(0,0,0,0.5);max-width:700px;margin:0 auto;">Space-themed learning games reinforce every lesson. Practice core concepts while having fun with interactive SCORM activities.</p></div>');
set_remui('sliderbuttontext3', 'Explore Activities');
set_remui('sliderurl3', '/course/');

set_remui('headeroverlayopacity', '60');
set_remui('frontpageheadercolor', '#0a1929');


// ═══════════════════════════════════════════════════════════════════
// STEP 4: FEATURE BLOCKS (4 blocks)
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 4: Feature Blocks ────────────────────────\n";

set_remui('frontpageblockdisplay', '1');
set_remui('enablesectionbutton', '1');
set_remui('frontpageblockheading', 'Why Choose Our Academy?');
set_remui('frontpageblockdesc', 'A complete K-8 learning experience designed for the modern student.');

// Block 1: Self-Paced Learning
set_remui('frontpageblocksection1', 'Self-Paced Learning');
set_remui('frontpageblockdescriptionsection1', 'Students learn at their own speed with no rigid schedules. Each course is designed to let learners progress as they master concepts, with full access to revisit any lesson at any time.');
set_remui('frontpageblockiconsection1', 'clock');
set_remui('sectionbuttontext1', 'Learn More');
set_remui('sectionbuttonlink1', '/course/');

// Block 2: Interactive Video Lessons
set_remui('frontpageblocksection2', 'Interactive Video Lessons');
set_remui('frontpageblockdescriptionsection2', 'Every lesson is delivered through professionally narrated video with embedded quiz checkpoints, chapter navigation, and closed captions — keeping students engaged and accountable.');
set_remui('frontpageblockiconsection2', 'play-circle');
set_remui('sectionbuttontext2', 'Learn More');
set_remui('sectionbuttonlink2', '/course/');

// Block 3: Hands-On Learning Games
set_remui('frontpageblocksection3', 'Hands-On Learning Games');
set_remui('frontpageblockdescriptionsection3', 'Fun, space-themed interactive games after every lesson. Students practice and consolidate key concepts through SCORM-based activities that adapt to their skill level.');
set_remui('frontpageblockiconsection3', 'gamepad');
set_remui('sectionbuttontext3', 'Learn More');
set_remui('sectionbuttonlink3', '/course/');

// Block 4: Ontario Curriculum Aligned
set_remui('frontpageblocksection4', 'Ontario Curriculum Aligned');
set_remui('frontpageblockdescriptionsection4', 'All 87 courses across 8 subjects are fully aligned with Ontario Ministry of Education curriculum expectations. Every learning activity maps directly to specific curriculum standards.');
set_remui('frontpageblockiconsection4', 'check-circle');
set_remui('sectionbuttontext4', 'Learn More');
set_remui('sectionbuttonlink4', '/course/');


// ═══════════════════════════════════════════════════════════════════
// STEP 5: ABOUT US SECTION
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 5: About Us Section ──────────────────────\n";

set_remui('enablefrontpageaboutus', '1');
set_remui('frontpageaboutusheading', 'About Our Academy');
set_remui('frontpageaboutustext', '<div style="max-width:800px;margin:0 auto;text-align:center;"><p style="font-size:1.15em;line-height:1.7;color:#37474f;">The <strong>Grade 1-8 Virtual Academy</strong> offers a comprehensive, self-paced online education experience for students in Grades 1 through 8. Our platform covers <strong>8 subject areas</strong> — Mathematics, Language, Science & Technology, Social Studies, Health & Physical Education, The Arts, and French — with <strong>87 complete courses</strong> featuring over 900 interactive lessons.</p><p style="font-size:1.05em;color:#546e7a;margin-top:16px;">Each lesson combines professionally narrated video instruction, interactive quiz checkpoints, and hands-on SCORM learning games. All content is mapped to the <strong>Ontario Ministry of Education curriculum expectations</strong>, ensuring students receive a rigorous, standards-aligned education from the comfort of home.</p></div>');


// ═══════════════════════════════════════════════════════════════════
// STEP 6: TESTIMONIALS
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 6: Testimonials ──────────────────────────\n";

set_remui('testimonialcount', '3');

set_remui('testimonialname1', 'Sarah M.');
set_remui('testimonialdesignation1', 'Parent of Grade 3 Student');
set_remui('testimonialtext1', 'My daughter absolutely loves the interactive video lessons. The embedded quizzes keep her engaged, and the space-themed games make practicing math feel like play. The self-paced format means she can take her time on challenging topics.');

set_remui('testimonialname2', 'David K.');
set_remui('testimonialdesignation2', 'Parent of Grade 6 Student');
set_remui('testimonialtext2', 'As a parent who homeschools, having access to a full Ontario curriculum-aligned program is invaluable. The course outlines, assessments, and learning logs give me everything I need to track my son\'s progress.');

set_remui('testimonialname3', 'Maria L.');
set_remui('testimonialdesignation3', 'Educator & Curriculum Consultant');
set_remui('testimonialtext3', 'The quality of the SCORM activities and interactive videos is outstanding. Every lesson maps directly to specific Ontario curriculum expectations — this isn\'t generic content, it\'s purpose-built for our standards.');


// ═══════════════════════════════════════════════════════════════════
// STEP 7: COURSE DISPLAY SETTINGS
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 7: Course Display ────────────────────────\n";

set_remui('courseperpage', '12');
set_remui('courseanimation', 'none');
set_remui('enablecoursestats', '1');
set_remui('showlatestcourse', '1');
set_remui('latestcoursecount', '12');
set_remui('showcoursepricing', '0');
set_remui('lessonsvisiblityoncoursecard', '1');
set_remui('coursedatevisibility', 'showupdatedate');
set_remui('courseheaderdesign', '1');
set_remui('showrelatedcourse', '1');
set_remui('enrolleduserscountvisibility', '1');


// ═══════════════════════════════════════════════════════════════════
// STEP 8: BRANDING & COLOR
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 8: Branding & Color ──────────────────────\n";

set_remui('fontselect', '1');
set_remui('fontname', 'Inter');
set_remui('pagewidth', 'default');
set_remui('enablesiteloader', '0');
set_remui('poweredby', '0');
set_remui('enablefeedback', '0');
set_remui('enableedwfeedback', '0');
set_remui('enablehelpsupport', '0');
set_remui('enableproductnotification', '0');
set_remui('enableusagetracking', '0');

// Disable announcement bar
set_remui('enableannouncement', '0');

// Dark mode support
set_remui('enabledarkmode', '0');


// ═══════════════════════════════════════════════════════════════════
// STEP 9: CUSTOM CSS ADDITIONS
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 9: Custom CSS ────────────────────────────\n";

// Get existing custom CSS and append our front page enhancements
$existing_css = get_config('theme_remui', 'customcss') ?? '';

$frontpage_css = <<<'CSS'

/* ═══ GRADE 1-8 VIRTUAL ACADEMY — FRONT PAGE ENHANCEMENTS ═══ */

/* Hero slider text enhancement */
.slider-section .carousel-caption {
    bottom: 20% !important;
    padding: 30px;
}

/* Feature blocks styling */
.frontpage-blocks .block-card {
    border-radius: 16px !important;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: none !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.frontpage-blocks .block-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}
.frontpage-blocks .block-card .icon-wrap {
    background: linear-gradient(135deg, #1a237e, #00838f) !important;
    color: #fff !important;
    width: 64px;
    height: 64px;
    border-radius: 16px !important;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* About Us section */
.frontpage-aboutus {
    background: linear-gradient(135deg, #f8f9fa 0%, #e8eaf6 100%) !important;
    padding: 60px 0 !important;
}

/* Testimonials modern styling */
.testimonial-section {
    background: #fafafa !important;
    padding: 60px 0 !important;
}
.testimonial-section .testimonial-card {
    border-radius: 16px !important;
    border: none !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}

/* Course cards styling */
.course-card {
    border-radius: 12px !important;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

/* Smooth page transitions */
* {
    scroll-behavior: smooth;
}

/* Better typography */
body {
    letter-spacing: 0.01em;
}
h1, h2, h3 {
    font-weight: 700 !important;
}

/* Hide Edwiser branding remnants for non-admin */
body:not(.role-admin) .edw-feedback-btn,
body:not(.role-admin) .edwiser-helpbar,
body:not(.role-admin) .edwiser-poweredby {
    display: none !important;
}

/* ═══ END FRONT PAGE ENHANCEMENTS ═══ */
CSS;

// Only add if not already present
if (strpos($existing_css, 'FRONT PAGE ENHANCEMENTS') === false) {
    set_remui('customcss', $existing_css . $frontpage_css);
    echo "  ✅ Custom CSS appended\n";
} else {
    echo "  ⏭ Custom CSS already contains front page enhancements\n";
}


// ═══════════════════════════════════════════════════════════════════
// STEP 10: PURGE CACHES
// ═══════════════════════════════════════════════════════════════════
echo "\n─── Step 10: Purge Caches ─────────────────────────\n";

// Bump theme revision to force CSS reload
theme_reset_all_caches();
echo "  ✅ All theme caches purged\n";

// Also purge general caches
purge_all_caches();
echo "  ✅ All Moodle caches purged\n";


// ═══════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════\n";
echo "  ✅ FRONT PAGE CONFIGURATION COMPLETE\n";
echo "═══════════════════════════════════════════════════\n";
echo "\nConfigured sections:\n";
echo "  • Site identity (name, summary, logo)\n";
echo "  • Hero slider (3 slides with overlay text & CTA)\n";
echo "  • Feature blocks (4 blocks: Self-Paced, Video, Games, Curriculum)\n";
echo "  • About Us section\n";
echo "  • Testimonials (3 cards)\n";
echo "  • Course display settings\n";
echo "  • Branding & typography (Inter font, custom colors)\n";
echo "  • Custom CSS enhancements\n";
echo "\nVisit: {$CFG->wwwroot}/ to see the updated front page.\n";
