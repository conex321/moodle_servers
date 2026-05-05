<?php
/**
 * FULL RemUI Theme Configuration — Grade 1-8 Virtual Academy
 *
 * Forest Green Theme — matching virtualelementaryschool.com aesthetic
 *
 * Configures the complete front page using RemUI's built-in sections:
 *   1. Hero Slider (3 slides with uploaded images)
 *   2. Feature Blocks (4 cards)
 *   3. About Us Section
 *   4. Testimonials (3 cards)
 *   5. Branding (logo, colors, custom CSS)
 *   6. Disable dark mode workaround
 *
 * Usage: docker exec moodle-app php /tmp/configure_remui_full.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

global $CFG, $DB;

echo "═══════════════════════════════════════════════════════════\n";
echo "  RemUI Full Front Page — Grade 1-8 Virtual Academy\n";
echo "  Theme: Forest Green\n";
echo "═══════════════════════════════════════════════════════════\n\n";


// ═══ HELPER: Upload image to theme_remui filearea ═══════════════
function upload_remui_image($filepath, $filearea, $filename) {
    $fs = get_file_storage();
    $syscontext = context_system::instance();

    // Clear existing files in this area
    $fs->delete_area_files($syscontext->id, 'theme_remui', $filearea);

    if (!file_exists($filepath)) {
        echo "    ⚠ File not found: $filepath\n";
        return '';
    }

    $filerecord = [
        'contextid' => $syscontext->id,
        'component' => 'theme_remui',
        'filearea'  => $filearea,
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => $filename,
    ];

    $fs->create_file_from_pathname($filerecord, $filepath);
    return '/' . $filename;
}


// ═══ STEP 1: ENABLE REMUI FRONT PAGE CHOOSER ══════════════════
echo "─── Step 1: Enable RemUI Front Page ─────────────────────\n";
set_config('frontpagechooser', '0', 'theme_remui');       // 0 = Legacy front page (slider + about + testimonials)
set_config('frontpageimagecontent', '1', 'theme_remui');  // 1 = dynamic slider mode
set_config('contenttype', '1', 'theme_remui');             // 1 = static image content
set_config('homepagetransparentheader', '0', 'theme_remui'); // 0 = solid header (not transparent)
set_config('frontpageheadercolor', '#ffffff', 'theme_remui'); // header text color
echo "  ✅ frontpagechooser = 0 (LEGACY — enables slider, about us, testimonials)\n";
echo "  ✅ frontpageimagecontent = 1 (dynamic slider)\n";
echo "  ✅ contenttype = 1 (static)\n";


// ═══ STEP 2: HERO SLIDER ═══════════════════════════════════════
echo "\n─── Step 2: Hero Slider (3 Slides) ────────────────────────\n";

set_config('slidercount', '3', 'theme_remui');
set_config('sliderautoplay', '1', 'theme_remui');
set_config('slideinterval', '6000', 'theme_remui');

// Slide 1 — Main brand slide
$img1 = upload_remui_image('/tmp/frontpage_images/hero1.png', 'slideimage1', 'hero_banner_1.png');
set_config('slideimage1', $img1, 'theme_remui');
set_config('slidertext1', '<div style="text-align:center;"><h1 style="font-size:2.6em;font-weight:800;color:#ffffff;text-shadow:0 3px 20px rgba(0,0,0,0.5);margin-bottom:16px;letter-spacing:-0.02em;">Grade 1-8 Virtual Academy</h1><p style="font-size:1.25em;color:#e8f5e9;max-width:680px;margin:0 auto;line-height:1.6;text-shadow:0 2px 8px rgba(0,0,0,0.4);">Ontario curriculum-aligned, self-paced online learning. Interactive video lessons, hands-on games, and comprehensive assessments for Grades 1–8.</p></div>', 'theme_remui');
set_config('sliderbuttontext1', 'Browse All Courses', 'theme_remui');
set_config('sliderurl1', '/course/', 'theme_remui');
echo "  ✅ Slide 1: Main brand hero (image: $img1)\n";

// Slide 2 — Interactive video
$img2 = upload_remui_image('/tmp/frontpage_images/hero2.png', 'slideimage2', 'hero_banner_2.png');
set_config('slideimage2', $img2, 'theme_remui');
set_config('slidertext2', '<div style="text-align:center;"><h1 style="font-size:2.6em;font-weight:800;color:#ffffff;text-shadow:0 3px 20px rgba(0,0,0,0.5);margin-bottom:16px;">Interactive Video Lessons</h1><p style="font-size:1.25em;color:#e8f5e9;max-width:680px;margin:0 auto;line-height:1.6;text-shadow:0 2px 8px rgba(0,0,0,0.4);">Professionally narrated lessons with embedded quizzes, chapter navigation, and progress tracking — learn at your own pace.</p></div>', 'theme_remui');
set_config('sliderbuttontext2', 'Start Learning Today', 'theme_remui');
set_config('sliderurl2', '/course/', 'theme_remui');
echo "  ✅ Slide 2: Interactive video (image: $img2)\n";

// Slide 3 — Learning games
$img3 = upload_remui_image('/tmp/frontpage_images/hero3.png', 'slideimage3', 'hero_banner_3.png');
set_config('slideimage3', $img3, 'theme_remui');
set_config('slidertext3', '<div style="text-align:center;"><h1 style="font-size:2.6em;font-weight:800;color:#ffffff;text-shadow:0 3px 20px rgba(0,0,0,0.5);margin-bottom:16px;">Hands-On Learning Games</h1><p style="font-size:1.25em;color:#e8f5e9;max-width:680px;margin:0 auto;line-height:1.6;text-shadow:0 2px 8px rgba(0,0,0,0.4);">Space-themed SCORM games reinforce every lesson — students practice concepts while having fun exploring the galaxy.</p></div>', 'theme_remui');
set_config('sliderbuttontext3', 'Explore Activities', 'theme_remui');
set_config('sliderurl3', '/course/', 'theme_remui');
echo "  ✅ Slide 3: Learning games (image: $img3)\n";

// Clear unused slides
for ($i = 4; $i <= 5; $i++) {
    set_config("slideimage$i", '', 'theme_remui');
    set_config("slidertext$i", '', 'theme_remui');
    set_config("sliderbuttontext$i", '', 'theme_remui');
    set_config("sliderurl$i", '', 'theme_remui');
}


// ═══ STEP 3: FEATURE BLOCKS ═══════════════════════════════════
echo "\n─── Step 3: Feature Blocks (4 Cards) ──────────────────────\n";

set_config('frontpageblockdisplay', '2', 'theme_remui');   // 1=disabled, 2=in-row, 3=in-column
set_config('enablesectionbutton', '1', 'theme_remui');     // Enable section buttons
set_config('frontpageblockheading', 'Why Choose Our Academy?', 'theme_remui');
set_config('frontpageblockdesc', 'A complete Grades 1–8 learning experience, built for today\'s digital learners.', 'theme_remui');

// Block 1: Self-Paced
set_config('frontpageblockiconsection1', 'clock', 'theme_remui');
set_config('frontpageblocksection1', 'Self-Paced Learning', 'theme_remui');
set_config('frontpageblockdescriptionsection1', 'Students learn at their own speed with no rigid schedules. Every lesson is available 24/7 — revisit, rewatch, and review anytime, anywhere.', 'theme_remui');
echo "  ✅ Block 1: Self-Paced Learning\n";

// Block 2: Interactive Video
set_config('frontpageblockiconsection2', 'play-circle', 'theme_remui');
set_config('frontpageblocksection2', 'Interactive Video Lessons', 'theme_remui');
set_config('frontpageblockdescriptionsection2', 'Every lesson is delivered through professionally narrated videos with embedded quiz checkpoints, chapter navigation, and closed captions.', 'theme_remui');
echo "  ✅ Block 2: Interactive Video Lessons\n";

// Block 3: Learning Games
set_config('frontpageblockiconsection3', 'gamepad', 'theme_remui');
set_config('frontpageblocksection3', 'Hands-On Learning Games', 'theme_remui');
set_config('frontpageblockdescriptionsection3', 'Space-themed interactive SCORM games after every lesson. Students practice core concepts through fun, guided gameplay.', 'theme_remui');
echo "  ✅ Block 3: Hands-On Learning Games\n";

// Block 4: Curriculum
set_config('frontpageblockiconsection4', 'check-circle', 'theme_remui');
set_config('frontpageblocksection4', 'Ontario Curriculum Aligned', 'theme_remui');
set_config('frontpageblockdescriptionsection4', 'All 55 courses across 8 subjects are fully aligned with Ontario Ministry of Education standards and expectations.', 'theme_remui');
echo "  ✅ Block 4: Ontario Curriculum Aligned\n";


// ═══ STEP 4: ABOUT US SECTION ═══════════════════════════════════
echo "\n─── Step 4: About Us Section ──────────────────────────────\n";

set_config('enablefrontpageaboutus', '1', 'theme_remui');
set_config('frontpageaboutusheading', 'About Our Academy', 'theme_remui');
set_config('frontpageaboutustext', '<div style="max-width:800px;margin:0 auto;text-align:center;"><p style="font-size:1.1em;line-height:1.8;color:#37474f;">The <strong>Grade 1-8 Virtual Academy</strong> is a comprehensive, self-paced online learning platform offering <strong>55 complete courses</strong> across <strong>8 subject areas</strong> — Mathematics, Language, Science &amp; Technology, Social Studies, Health &amp; Physical Education, The Arts, and French.</p><p style="font-size:1em;line-height:1.7;color:#546e7a;margin-top:16px;">Each lesson combines professionally narrated video instruction, interactive quiz checkpoints, and hands-on SCORM learning games. All content is fully mapped to the <strong>Ontario Ministry of Education</strong> curriculum expectations.</p></div>', 'theme_remui');

// Upload about image
$aboutimg = upload_remui_image('/tmp/frontpage_images/about.png', 'frontpageaboutusimage', 'about_us.png');
set_config('frontpageaboutusimage', $aboutimg, 'theme_remui');
echo "  ✅ About Us heading, text, and image configured\n";


// ═══ STEP 5: TESTIMONIALS ═══════════════════════════════════════
echo "\n─── Step 5: Testimonials (3 Cards) ────────────────────────\n";

set_config('testimonialcount', '3', 'theme_remui');

set_config('testimonialname1', 'Sarah M.', 'theme_remui');
set_config('testimonialdesignation1', 'Parent of Grade 3 Student', 'theme_remui');
set_config('testimonialtext1', 'My daughter absolutely loves the interactive video lessons. The embedded quizzes keep her engaged throughout each lesson, and the space-themed games make practicing math feel like play rather than work.', 'theme_remui');
echo "  ✅ Testimonial 1: Sarah M.\n";

set_config('testimonialname2', 'David K.', 'theme_remui');
set_config('testimonialdesignation2', 'Parent of Grade 6 Student', 'theme_remui');
set_config('testimonialtext2', 'As a parent who homeschools, having access to a full Ontario curriculum-aligned program is invaluable. The detailed assessments and learning activity logs give me everything I need to track my son\'s progress.', 'theme_remui');
echo "  ✅ Testimonial 2: David K.\n";

set_config('testimonialname3', 'Maria L.', 'theme_remui');
set_config('testimonialdesignation3', 'Educator & Curriculum Consultant', 'theme_remui');
set_config('testimonialtext3', 'The quality of the SCORM activities and interactive videos is outstanding. Every single lesson maps directly to specific Ontario curriculum expectations, which is rare in online platforms.', 'theme_remui');
echo "  ✅ Testimonial 3: Maria L.\n";


// ═══ STEP 6: BRANDING & COLORS ═══════════════════════════════════
echo "\n─── Step 6: Branding & Colors ─────────────────────────────\n";

// Site name
$site = $DB->get_record('course', ['id' => 1]);
$site->fullname = 'Grade 1-8 Virtual Academy';
$site->shortname = 'G18VA';
$site->summary = 'Ontario curriculum-aligned, self-paced online learning for Grades 1 through 8.';
$site->summaryformat = FORMAT_HTML;
$DB->update_record('course', $site);
echo "  ✅ Site name: Grade 1-8 Virtual Academy\n";

// Header colors — FOREST GREEN
set_config('frontpageheadercolor', '#0e3d1f', 'theme_remui');
set_config('headeroverlayopacity', '55', 'theme_remui');
echo "  ✅ Header color: #0e3d1f (dark forest green)\n";
echo "  ✅ Overlay opacity: 55%\n";

// Upload logos
$fs = get_file_storage();
$syscontext = context_system::instance();

$logopath = '/tmp/frontpage_images/logo.png';
if (file_exists($logopath)) {
    // Core admin logo
    $fs->delete_area_files($syscontext->id, 'core_admin', 'logo');
    $fs->create_file_from_pathname([
        'contextid' => $syscontext->id,
        'component' => 'core_admin',
        'filearea'  => 'logo',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => 'school_logo.png',
    ], $logopath);
    set_config('logo', '/school_logo.png', 'core_admin');

    // Compact logo
    $fs->delete_area_files($syscontext->id, 'core_admin', 'logocompact');
    $fs->create_file_from_pathname([
        'contextid' => $syscontext->id,
        'component' => 'core_admin',
        'filearea'  => 'logocompact',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => 'school_logo_compact.png',
    ], $logopath);
    set_config('logocompact', '/school_logo_compact.png', 'core_admin');

    // RemUI logo
    upload_remui_image($logopath, 'logo', 'remui_logo.png');
    set_config('logo', '/remui_logo.png', 'theme_remui');

    echo "  ✅ Logo uploaded (core + remui)\n";
}


// ═══ STEP 7: CUSTOM CSS — FOREST GREEN THEME ═══════════════════
echo "\n─── Step 7: Custom CSS (Forest Green) ─────────────────────\n";

$css = <<<'CUSTOMCSS'
/* ═══ GRADE 1-8 VIRTUAL ACADEMY — FOREST GREEN THEME ═══ */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* CSS Custom Properties */
:root {
    --g18-primary: #167e3f;
    --g18-primary-dark: #0e3d1f;
    --g18-primary-light: #1a9b4f;
    --g18-accent: #f5a623;
    --g18-accent-light: #ffd54f;
    --g18-green-bright: #2ecc71;
    --g18-green-pale: #e8f5e9;
    --g18-green-hover: #a8e6cf;
    --g18-text-dark: #1a1a2e;
    --g18-text-medium: #444;
    --g18-text-light: #666;
    --g18-bg-light: #f8f9fa;
}

/* Inter font everywhere */
body, .navbar, h1, h2, h3, h4, h5, h6, p, .card-text, .card-title {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

/* ── Navbar — FORCE dark forest green ── */
header#header.navbar,
header.navbar,
.navbar,
nav.navbar,
body header.navbar,
body #header.navbar,
body nav.navbar.fixed-top,
html body header.navbar {
    background: linear-gradient(135deg, #0e3d1f 0%, #145a2e 100%) !important;
    border-bottom: 3px solid #2ecc71 !important;
    box-shadow: 0 2px 12px rgba(0,0,0,0.25) !important;
}

/* ── RemUI inner navbar elements — FORCE transparent ── */
.navbar .sub-nav,
.navbar .menu-wrapper,
.navbar .navbar-brand,
.navbar .category-wrapper,
.navbar .edw-nav-container,
.navbar .navbar-nav,
.navbar .edw-topbar,
.navbar .nav-item,
nav.navbar .sub-nav,
nav.navbar .menu-wrapper,
nav.navbar .navbar-brand,
nav.navbar .category-wrapper,
body nav.navbar .sub-nav,
body nav.navbar .menu-wrapper,
body nav.navbar .navbar-brand,
body nav.navbar .category-wrapper {
    background: transparent !important;
    background-color: transparent !important;
}

/* ALL navbar text — force white/light for dark green bg */
header.navbar a,
header.navbar .nav-link,
header.navbar .dropdown-toggle,
header.navbar .navbar-brand span,
header.navbar .moremenu .nav-link,
.navbar a,
.navbar .nav-link,
.navbar .dropdown-toggle,
.navbar .navbar-brand,
.navbar .navbar-brand span,
.navbar .moremenu .nav-link,
.navbar .popover-region-toggle,
.navbar #usernavigation a,
.navbar #usernavigation .nav-link,
.navbar .primary-navigation .nav-link,
.navbar .primary-navigation a,
.navbar .custom-menus a,
.navbar .catselector-menu,
.navbar .category-wrapper *,
nav.moremenu .nav-link,
body header .nav-link,
body header .dropdown-toggle,
.edw-drawer-toggle-btn {
    color: #e8f5e9 !important;
}
header.navbar a:hover,
.navbar a:hover,
.navbar .nav-link:hover,
.navbar .dropdown-toggle:hover,
.navbar .primary-navigation .nav-link:hover,
body header .nav-link:hover {
    color: #a8e6cf !important;
}

/* Login link */
.navbar .login a,
.navbar a[href*="login"],
#usernavigation a[href*="login"] {
    color: #ffffff !important;
    font-weight: 600 !important;
}

/* Category dropdown button */
.navbar .btn-outline-secondary,
.navbar .btn[aria-haspopup] {
    color: #e8f5e9 !important;
    border-color: rgba(255,255,255,0.3) !important;
}
.navbar .btn-outline-secondary:hover,
.navbar .btn[aria-haspopup]:hover {
    color: #ffffff !important;
    border-color: #a8e6cf !important;
    background: rgba(255,255,255,0.1) !important;
}

/* ── Hero slider text legibility ── */
.carousel-item .slider-desc-wrapper h1,
.carousel-item .slider-desc-wrapper h2,
.carousel-item .slider-desc-wrapper p,
.slider-content h1, .slider-content p {
    text-shadow: 0 3px 20px rgba(0,0,0,0.6), 0 1px 4px rgba(0,0,0,0.4) !important;
}
/* Slider buttons — bright green */
.carousel-item .btn, .slider-desc-wrapper .btn {
    background: linear-gradient(135deg, #2ecc71, #27ae60) !important;
    color: #ffffff !important;
    border: none !important;
    font-weight: 700 !important;
    padding: 12px 32px !important;
    border-radius: 30px !important;
    box-shadow: 0 4px 16px rgba(46,204,113,0.4) !important;
    text-shadow: none !important;
    font-size: 1.05em !important;
}
.carousel-item .btn:hover, .slider-desc-wrapper .btn:hover {
    background: linear-gradient(135deg, #58d68d, #2ecc71) !important;
    transform: translateY(-2px);
}

/* ── Feature block cards ── */
.frontpage-blocks .card, .feature-block-card {
    border-radius: 16px !important;
    border: 1px solid #e8f5e9 !important;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.frontpage-blocks .card:hover, .feature-block-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(22,126,63,0.15);
}

/* ── Course cards ── */
.card.coursebox, .course-card {
    border-radius: 14px !important;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e8f5e9 !important;
}
.card.coursebox:hover, .course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(22,126,63,0.12);
}

/* ── About Us section ── */
.frontpage-aboutus, .aboutus-wrapper {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%) !important;
}
.frontpage-aboutus h2, .aboutus-wrapper h2 {
    color: #0d472a !important;
    font-weight: 700 !important;
}

/* ── Testimonials ── */
.carousel-control-prev-icon, .carousel-control-next-icon {
    background-color: rgba(14, 61, 31, 0.6);
    border-radius: 50%;
    padding: 20px;
}

/* ── Section headings ── */
.frontpage-blocks h2, h2.sectionname, .section-heading h2 {
    color: #0d472a !important;
    font-weight: 700 !important;
}

/* ── Footer — dark forest green ── */
#page-footer, footer {
    background: #0e3d1f !important;
    color: #b0bec5 !important;
}
#page-footer a, footer a {
    color: #a8e6cf !important;
}

/* ── Buttons — forest green gradient ── */
.btn-primary {
    background: linear-gradient(135deg, #167e3f, #0e3d1f) !important;
    border: none !important;
    border-radius: 8px !important;
    font-weight: 600;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #1a9b4f, #167e3f) !important;
    box-shadow: 0 4px 12px rgba(22,126,63,0.3);
}

/* ── Remove Edwiser branding ── */
.edwiser-footer-info,
a[href*="edwiser.org"],
a[href*="theme_remui"],
.powered-by-edwiser,
.remui-branding {
    display: none !important;
}

/* ── Smooth scroll ── */
html { scroll-behavior: smooth; }

/* ── Global fluid body text ── */
body {
    font-size: clamp(0.875rem, 0.8vw + 0.5rem, 1rem);
}

/* ═══ DASHBOARD (my-index) ═══ */
#page-my-index .block {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e8f5e9;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    transition: box-shadow 0.3s ease;
}
#page-my-index .block:hover {
    box-shadow: 0 6px 20px rgba(22,126,63,0.1);
}
#page-my-index .block .card-body {
    padding: clamp(12px, 2vw, 20px);
}
#page-my-index .block .card-title,
#page-my-index .block .card-header {
    font-weight: 700;
    color: #0d472a;
}
/* Dashboard course cards */
#page-my-index .block_recentlyaccessedcourses .card,
#page-my-index .block_myoverview .card,
#page-my-index .block_myoverview .course-info-container {
    border-radius: 10px !important;
    border: 1px solid #e8f5e9 !important;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
#page-my-index .block_recentlyaccessedcourses .card:hover,
#page-my-index .block_myoverview .card:hover,
#page-my-index .block_myoverview .course-info-container:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(22,126,63,0.12);
}
/* Dashboard headings */
#page-my-index h2,
#page-my-index .card-title {
    font-size: clamp(1.1rem, 1.5vw + 0.3rem, 1.4rem);
}
/* Dashboard timeline styling */
#page-my-index .block_timeline .list-group-item {
    border-radius: 8px;
    margin-bottom: 4px;
    border: 1px solid #f0f0f0;
}
/* Calendar accent */
#page-my-index .block_calendar_month .calendar_event_course {
    border-left: 3px solid #167e3f;
}

/* ═══ MY COURSES (/my/courses.php) ═══ */
#page-my-courses .coursebox,
#page-my-courses .card,
.course-summaryitem {
    border-radius: 12px !important;
    border: 1px solid #e8f5e9 !important;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
#page-my-courses .coursebox:hover,
#page-my-courses .card:hover,
.course-summaryitem:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(22,126,63,0.12);
}
#page-my-courses h3.coursename,
#page-my-courses .course-info-container .coursename {
    font-size: clamp(1rem, 1.2vw + 0.3rem, 1.2rem);
    font-weight: 600;
    color: #0d472a;
}
#page-my-courses .course-info-container .coursename a {
    color: #0d472a !important;
}
#page-my-courses .course-info-container .coursename a:hover {
    color: #167e3f !important;
}
/* Course overview filter tabs */
#page-my-courses .nav-tabs .nav-link.active,
#page-my-index .block_myoverview .nav-tabs .nav-link.active {
    border-bottom: 3px solid #167e3f !important;
    color: #167e3f !important;
    font-weight: 600;
}

/* ═══ SITE ADMINISTRATION ═══ */
.path-admin .generalbox,
.path-admin .settingsform .mform {
    border-radius: 10px;
    overflow: hidden;
}
.path-admin h2 {
    font-size: clamp(1.3rem, 2vw + 0.3rem, 1.8rem);
    color: #0d472a;
    font-weight: 700;
}
.path-admin .card {
    border-radius: 10px !important;
    border: 1px solid #e8f5e9;
}
#page-admin-index .card {
    border-radius: 10px;
    transition: box-shadow 0.3s;
}
#page-admin-index .card:hover {
    box-shadow: 0 4px 16px rgba(22,126,63,0.08);
}
/* Admin search bar */
.path-admin #adminsearchquery {
    border-radius: 25px;
    border: 2px solid #e0e0e0;
    padding: 10px 20px;
    transition: border-color 0.3s;
}
.path-admin #adminsearchquery:focus {
    border-color: #167e3f;
    box-shadow: 0 0 0 3px rgba(22,126,63,0.1);
}

/* ═══ GLOBAL RESPONSIVE IMPROVEMENTS ═══ */
/* Page builder pages — ensure full-width on mobile */
.edwiserpbf-page #page-content,
.edwiserpbf-page .course-content {
    max-width: 100%;
    padding: 0;
}
/* Better card grid on course listing page */
#page-course-index-category .coursebox {
    border-radius: 12px !important;
    border: 1px solid #e8f5e9 !important;
    transition: transform 0.3s, box-shadow 0.3s;
}
#page-course-index-category .coursebox:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(22,126,63,0.1);
}

/* ═══ END FOREST GREEN THEME ═══ */
CUSTOMCSS;

set_config('customcss', $css, 'theme_remui');
echo "  ✅ Custom CSS injected (" . strlen($css) . " chars)\n";


// ═══ STEP 8: ADDITIONAL HTML (CSS injection + font) ══
echo "\n─── Step 8: Additional HTML Head ───────────────────────────\n";
$head = '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

// ALSO inject CSS via <style> tag directly in head for guaranteed application
$head .= '<style type="text/css">' . $css . '</style>';

set_config('additionalhtmlhead', $head);
// Clear out any old topofbody injection
set_config('additionalhtmltopofbody', '');
echo "  ✅ Inter font preloaded\n";
echo "  ✅ CSS injected via <style> in head (" . strlen($css) . " chars)\n";
echo "  ✅ Cleared old topofbody injections\n";


// ═══ STEP 9: SECURITY — Public Front Page ═══════════════════════
echo "\n─── Step 9: Public Access ─────────────────────────────────\n";
set_config('forcelogin', '0');
echo "  ✅ Force login disabled (public front page)\n";


// ═══ STEP 10: DARK MODE FIX ═══════════════════════════════════
echo "\n─── Step 10: Dark Mode Workaround ─────────────────────────\n";
set_config('enabledarkmode', '0', 'theme_remui');
echo "  ✅ Dark mode disabled (prevents trigger_dm_enabled crash)\n";


// ═══ STEP 11: FRONTPAGE ITEMS ═══════════════════════════════════
echo "\n─── Step 11: Moodle Core Frontpage Items ──────────────────\n";
set_config('frontpage', '6');
set_config('frontpageloggedin', '6');
set_config('defaulthomepage', '0');
set_config('frontpagecourselimit', '12');
echo "  ✅ Frontpage: course list\n";
echo "  ✅ Default home: site front page\n";


// ═══ STEP 12: PURGE EVERYTHING ═══════════════════════════════
echo "\n─── Step 12: Final Cache Purge ─────────────────────────────\n";
theme_reset_all_caches();
purge_all_caches();
echo "  ✅ All caches purged\n";


// ═══ SUMMARY ═══════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  ✅ REMUI FRONT PAGE CONFIGURATION COMPLETE\n";
echo "  🌿 Theme: Forest Green\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Configured sections:\n";
echo "  1. Hero Slider — 3 slides with pro images & CTA buttons\n";
echo "  2. Feature Blocks — 4 cards (Self-Paced, Video, Games, Curriculum)\n";
echo "  3. About Us — Heading + description + image\n";
echo "  4. Testimonials — 3 cards\n";
echo "  5. Branding — Logo, Inter font, forest green palette\n";
echo "  6. Custom CSS — Typography, card hovers, green theme\n";
echo "  7. Public access enabled\n";
echo "  8. Dark mode disabled\n\n";
echo "Visit: {$CFG->wwwroot}/\n";
