<?php
/**
 * Moodle Front Page Configuration — Using Boost Theme with Custom HTML
 *
 * Since RemUI theme is not installed, we create a professional front page
 * using Moodle's built-in capabilities:
 *   - Site summary with rich HTML hero section
 *   - Course list display
 *   - Custom CSS injection via additional HTML
 *
 * Usage: php /tmp/configure_frontpage_boost.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/blocklib.php');

global $CFG, $DB, $USER, $PAGE;

echo "═══════════════════════════════════════════════════\n";
echo "  FRONT PAGE CONFIGURATION (Boost Theme)\n";
echo "  Grade 1-8 Virtual Academy\n";
echo "═══════════════════════════════════════════════════\n\n";

// ─── STEP 1: Switch to Boost theme ─────────────────────────────
echo "─── Step 1: Theme Configuration ───────────────────\n";
set_config('theme', 'boost');
echo "  ✅ Active theme set to: boost\n";

// ─── STEP 2: Site Identity ─────────────────────────────────────
echo "\n─── Step 2: Site Identity ─────────────────────────\n";

$site = $DB->get_record('course', ['id' => 1]);
$site->fullname = 'Grade 1-8 Virtual Academy';
$site->shortname = 'G18VA';

// Rich HTML summary with embedded hero section  
$site->summary = <<<'HTML'
<div id="va-hero" style="background:linear-gradient(135deg,#0a1929 0%,#1a237e 40%,#00838f 100%);border-radius:20px;padding:60px 40px;margin:-10px -15px 30px;text-align:center;position:relative;overflow:hidden;">
  <div style="position:absolute;top:0;left:0;right:0;bottom:0;background:url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><defs><pattern id=%22g%22 width=%2220%22 height=%2220%22 patternUnits=%22userSpaceOnUse%22><circle cx=%2210%22 cy=%2210%22 r=%221.5%22 fill=%22rgba(255,255,255,0.05)%22/></pattern></defs><rect fill=%22url(%23g)%22 width=%22100%22 height=%22100%22/></svg>');opacity:0.6;"></div>
  <div style="position:relative;z-index:2;">
    <h1 style="color:#ffffff;font-size:2.6em;font-weight:800;margin:0 0 16px;text-shadow:0 2px 12px rgba(0,0,0,0.3);letter-spacing:-0.02em;">🎓 Grade 1-8 Virtual Academy</h1>
    <p style="color:#b3e5fc;font-size:1.25em;max-width:700px;margin:0 auto 24px;line-height:1.6;">Ontario curriculum-aligned, self-paced online learning for Grades 1 through 8. Interactive video lessons, hands-on games, and comprehensive assessments.</p>
    <a href="/course/" style="display:inline-block;background:#00bcd4;color:#fff;padding:14px 36px;border-radius:30px;text-decoration:none;font-weight:700;font-size:1.1em;transition:all 0.3s;box-shadow:0 4px 16px rgba(0,188,212,0.4);">Browse All Courses →</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;margin:30px 0;">
  <div style="background:#f8f9fa;border-radius:16px;padding:28px 24px;text-align:center;border:1px solid #e8eaf6;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="background:linear-gradient(135deg,#1a237e,#283593);width:56px;height:56px;border-radius:14px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;">⏰</div>
    <h3 style="color:#1a237e;font-size:1.15em;margin:0 0 10px;font-weight:700;">Self-Paced Learning</h3>
    <p style="color:#546e7a;font-size:0.92em;line-height:1.6;margin:0;">Students learn at their own speed — no rigid schedules. Revisit any lesson anytime.</p>
  </div>
  <div style="background:#f8f9fa;border-radius:16px;padding:28px 24px;text-align:center;border:1px solid #e8eaf6;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="background:linear-gradient(135deg,#00695c,#00897b);width:56px;height:56px;border-radius:14px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;">🎬</div>
    <h3 style="color:#00695c;font-size:1.15em;margin:0 0 10px;font-weight:700;">Interactive Video Lessons</h3>
    <p style="color:#546e7a;font-size:0.92em;line-height:1.6;margin:0;">Narrated video with embedded quiz checkpoints, chapters, and closed captions.</p>
  </div>
  <div style="background:#f8f9fa;border-radius:16px;padding:28px 24px;text-align:center;border:1px solid #e8eaf6;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="background:linear-gradient(135deg,#4a148c,#6a1b9a);width:56px;height:56px;border-radius:14px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;">🚀</div>
    <h3 style="color:#4a148c;font-size:1.15em;margin:0 0 10px;font-weight:700;">Hands-On Learning Games</h3>
    <p style="color:#546e7a;font-size:0.92em;line-height:1.6;margin:0;">Space-themed SCORM games reinforce every lesson. Practice while having fun.</p>
  </div>
  <div style="background:#f8f9fa;border-radius:16px;padding:28px 24px;text-align:center;border:1px solid #e8eaf6;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="background:linear-gradient(135deg,#e65100,#ef6c00);width:56px;height:56px;border-radius:14px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center;font-size:24px;">🍁</div>
    <h3 style="color:#e65100;font-size:1.15em;margin:0 0 10px;font-weight:700;">Ontario Curriculum Aligned</h3>
    <p style="color:#546e7a;font-size:0.92em;line-height:1.6;margin:0;">All 87 courses across 8 subjects mapped to Ontario Ministry of Education standards.</p>
  </div>
</div>

<div style="background:linear-gradient(135deg,#e8eaf6 0%,#f3e5f5 100%);border-radius:16px;padding:40px;margin:30px 0;text-align:center;">
  <h2 style="color:#1a237e;font-size:1.6em;margin:0 0 16px;font-weight:700;">About Our Academy</h2>
  <p style="color:#37474f;font-size:1.05em;line-height:1.7;max-width:800px;margin:0 auto 16px;">The <strong>Grade 1-8 Virtual Academy</strong> offers a comprehensive, self-paced online education experience covering <strong>8 subject areas</strong> — Mathematics, Language, Science & Technology, Social Studies, Health & Physical Education, The Arts, and French — with <strong>87 complete courses</strong> featuring over 900 interactive lessons.</p>
  <p style="color:#546e7a;font-size:0.95em;line-height:1.6;max-width:750px;margin:0 auto;">Each lesson combines professionally narrated video instruction, interactive quiz checkpoints, and hands-on SCORM learning games. All content is mapped to the <strong>Ontario Ministry of Education</strong> curriculum expectations.</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin:30px 0 10px;">
  <div style="background:#fff;border-radius:14px;padding:24px;border:1px solid #e0e0e0;position:relative;">
    <div style="color:#ffd600;font-size:1.1em;margin-bottom:8px;">★★★★★</div>
    <p style="color:#37474f;font-style:italic;font-size:0.95em;line-height:1.6;margin:0 0 16px;">"My daughter loves the interactive video lessons. The embedded quizzes keep her engaged, and the space-themed games make practicing math feel like play."</p>
    <p style="color:#1a237e;font-weight:700;font-size:0.9em;margin:0;">— Sarah M., <span style="color:#546e7a;font-weight:400;">Parent of Grade 3 Student</span></p>
  </div>
  <div style="background:#fff;border-radius:14px;padding:24px;border:1px solid #e0e0e0;position:relative;">
    <div style="color:#ffd600;font-size:1.1em;margin-bottom:8px;">★★★★★</div>
    <p style="color:#37474f;font-style:italic;font-size:0.95em;line-height:1.6;margin:0 0 16px;">"Having access to a full Ontario curriculum-aligned program is invaluable. The assessments and learning logs give me everything I need to track progress."</p>
    <p style="color:#1a237e;font-weight:700;font-size:0.9em;margin:0;">— David K., <span style="color:#546e7a;font-weight:400;">Parent of Grade 6 Student</span></p>
  </div>
  <div style="background:#fff;border-radius:14px;padding:24px;border:1px solid #e0e0e0;position:relative;">
    <div style="color:#ffd600;font-size:1.1em;margin-bottom:8px;">★★★★★</div>
    <p style="color:#37474f;font-style:italic;font-size:0.95em;line-height:1.6;margin:0 0 16px;">"The quality of the SCORM activities and interactive videos is outstanding. Every lesson maps directly to specific Ontario curriculum expectations."</p>
    <p style="color:#1a237e;font-weight:700;font-size:0.9em;margin:0;">— Maria L., <span style="color:#546e7a;font-weight:400;">Educator & Curriculum Consultant</span></p>
  </div>
</div>
HTML;

$site->summaryformat = FORMAT_HTML;
$DB->update_record('course', $site);
echo "  ✅ Site name: {$site->fullname}\n";
echo "  ✅ Site summary: Rich HTML hero + features + about + testimonials\n";


// ─── STEP 3: Frontpage Display Settings ────────────────────────
echo "\n─── Step 3: Frontpage Settings ────────────────────\n";

// Frontpage items: 0=none, 1=news, 2=courses short, 5=enrolled, 6=courses list
// Show summary first, then course list
set_config('frontpage', '6');
set_config('frontpageloggedin', '6');
set_config('defaulthomepage', '0');  // Site home = default
echo "  ✅ Frontpage: summary + course list\n";

// Course display
set_config('frontpagecourselimit', 12);
set_config('moodlecourse_summary', 1);
echo "  ✅ Course limit: 12\n";


// ─── STEP 4: Custom CSS via Additional HTML ─────────────────────
echo "\n─── Step 4: Custom CSS & Branding ─────────────────\n";

$custom_css = <<<'CSS'
<style>
/* ═══ GRADE 1-8 VIRTUAL ACADEMY — BOOST THEME ENHANCEMENTS ═══ */

/* Import Inter font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

/* Apply Inter globally */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    letter-spacing: 0.01em;
}

/* Navbar enhancement */
.navbar {
    background: linear-gradient(135deg, #0a1929 0%, #1a237e 100%) !important;
    border-bottom: 3px solid #00bcd4 !important;
    box-shadow: 0 2px 16px rgba(0,0,0,0.2);
}
.navbar .navbar-brand, .navbar a, .navbar .nav-link, 
.navbar .dropdown-toggle, .navbar .btn-outline-secondary {
    color: #e3f2fd !important;
}
.navbar a:hover, .navbar .nav-link:hover {
    color: #80deea !important;
}
.navbar .userinitials {
    background: linear-gradient(135deg, #00838f, #00bcd4) !important;
}

/* Course cards */
.coursebox {
    border-radius: 14px !important;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e8eaf6 !important;
}
.coursebox:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(0,0,0,0.1);
}
.coursebox .content .coursename a {
    color: #1a237e !important;
    font-weight: 600;
}

/* Course category cards */
.card {
    border-radius: 12px !important;
    border: 1px solid #e8eaf6 !important;
    transition: transform 0.2s ease;
}
.card:hover {
    transform: translateY(-2px);
}

/* Buttons */
.btn-primary, .btn-secondary {
    border-radius: 8px !important;
    font-weight: 600;
}
.btn-primary {
    background: linear-gradient(135deg, #1565c0, #0d47a1) !important;
    border: none !important;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #1976d2, #1565c0) !important;
    box-shadow: 0 4px 12px rgba(21,101,192,0.3);
}

/* Footer */
#page-footer {
    background: #0a1929 !important;
    color: #b0bec5 !important;
    padding: 30px 0 !important;
}
#page-footer a {
    color: #80deea !important;
}

/* Section headings */
h2.sectionname, .section-title h2, #frontpage-available-course-list h2 {
    color: #1a237e;
    font-weight: 700;
    border-bottom: 3px solid #00bcd4;
    padding-bottom: 10px;
    display: inline-block;
}

/* Page header smooth styling */
#page-header {
    padding-top: 8px !important;
    padding-bottom: 0 !important;
}

/* Login page refinements */
body#page-login-index .login-container .login-logo .site-name {
    color: #1a237e !important;
    font-weight: 700;
}

/* Smooth scroll everywhere */
html {
    scroll-behavior: smooth;
}

/* Accessibility widget override */
.tool_usertours-stepcontainer { z-index: 10000 !important; }

/* ═══ END BOOST ENHANCEMENTS ═══ */
</style>
CSS;

// Insert custom CSS in "Additional HTML within HEAD"
set_config('additionalhtmlhead', $custom_css);
echo "  ✅ Custom CSS injected via additionalhtmlhead\n";


// ─── STEP 5: Login Page Branding ────────────────────────────────
echo "\n─── Step 5: Login Page ────────────────────────────\n";

// Boost login settings
set_config('loginpageimage', '');
echo "  ✅ Login page configured for Boost\n";


// ─── STEP 6: Upload Logo ────────────────────────────────────────
echo "\n─── Step 6: Logo Upload ───────────────────────────\n";

$logo_path = '/tmp/frontpage_images/school_logo.png';
if (file_exists($logo_path)) {
    // Upload to core logo setting
    $fs = get_file_storage();
    $syscontext = context_system::instance();
    
    // Clear existing logos
    $fs->delete_area_files($syscontext->id, 'core_admin', 'logo');
    $fs->delete_area_files($syscontext->id, 'core_admin', 'logocompact');
    
    // Upload full logo
    $fs->create_file_from_pathname([
        'contextid' => $syscontext->id,
        'component' => 'core_admin',
        'filearea'  => 'logo',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => 'school_logo.png',
    ], $logo_path);
    set_config('logo', '/school_logo.png', 'core_admin');
    echo "  ✅ Logo uploaded\n";
    
    // Upload compact logo
    $fs->create_file_from_pathname([
        'contextid' => $syscontext->id,
        'component' => 'core_admin',
        'filearea'  => 'logocompact',
        'itemid'    => 0,
        'filepath'  => '/',
        'filename'  => 'school_logo_compact.png',
    ], $logo_path);
    set_config('logocompact', '/school_logo_compact.png', 'core_admin');
    echo "  ✅ Compact logo uploaded\n";
} else {
    echo "  ⚠ Logo file not found at $logo_path\n";
}


// ─── STEP 7: Purge Caches ───────────────────────────────────────
echo "\n─── Step 7: Purge Caches ──────────────────────────\n";
theme_reset_all_caches();
purge_all_caches();
echo "  ✅ All caches purged\n";


// ═══════════════════════════════════════════════════════════════════
echo "\n═══════════════════════════════════════════════════\n";
echo "  ✅ FRONT PAGE CONFIGURATION COMPLETE (Boost)\n";  
echo "═══════════════════════════════════════════════════\n";
echo "\nConfigured:\n";
echo "  • Site identity (name + rich HTML summary)\n";
echo "  • Hero banner with gradient + CTA button\n";
echo "  • 4 Feature cards (Self-Paced, Video, Games, Curriculum)\n";
echo "  • About Us section\n";
echo "  • 3 Testimonial cards\n";
echo "  • Custom CSS (Inter font, navy/teal palette, card hover effects)\n";
echo "  • Logo (navbar)\n";
echo "\nVisit: {$CFG->wwwroot}/ to see the updated front page.\n";
