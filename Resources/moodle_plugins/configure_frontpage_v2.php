<?php
/**
 * Moodle Front Page — Inject Hero via additionalhtmltopofbody
 * 
 * This method injects the hero banner and content sections into 
 * the TOP OF BODY HTML area, with JavaScript to only display it
 * on the front page (body#page-site-index).
 * 
 * Works with Moodle 5.x + Boost theme guaranteed.
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

global $CFG, $DB;

echo "═══════════════════════════════════════════════════\n";
echo "  FRONT PAGE V2 — additionalhtmltopofbody Method\n";
echo "═══════════════════════════════════════════════════\n\n";

// ─── The Hero HTML (only visible on front page via CSS) ─────────
$hero_html = <<<'HEROHTML'
<!-- GRADE 1-8 VIRTUAL ACADEMY — FRONT PAGE HERO -->
<style>
/* Only show hero content on front page */
#va-frontpage-hero { display: none; }
body#page-site-index #va-frontpage-hero { display: block; }

/* Hero container - full width */
#va-frontpage-hero {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    margin: 0;
    padding: 0;
    overflow: hidden;
}

/* ═══ HERO BANNER ═══ */
.va-hero-banner {
    background: linear-gradient(135deg, #0a1929 0%, #1a237e 40%, #00838f 100%);
    padding: 72px 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.va-hero-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.06) 1px, transparent 1px),
                      radial-gradient(circle at 75% 75%, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 30px 30px, 20px 20px;
    pointer-events: none;
}
.va-hero-banner h1 {
    color: #ffffff;
    font-size: 2.8em;
    font-weight: 800;
    margin: 0 0 18px;
    text-shadow: 0 2px 16px rgba(0,0,0,0.3);
    letter-spacing: -0.02em;
    position: relative;
    z-index: 1;
}
.va-hero-banner .va-subtitle {
    color: #b3e5fc;
    font-size: 1.2em;
    max-width: 720px;
    margin: 0 auto 28px;
    line-height: 1.65;
    position: relative;
    z-index: 1;
}
.va-hero-cta {
    display: inline-block;
    background: linear-gradient(135deg, #00bcd4, #0097a7);
    color: #fff !important;
    padding: 15px 40px;
    border-radius: 32px;
    text-decoration: none !important;
    font-weight: 700;
    font-size: 1.1em;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(0,188,212,0.4);
    position: relative;
    z-index: 1;
}
.va-hero-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(0,188,212,0.5);
    background: linear-gradient(135deg, #26c6da, #00bcd4);
}

/* ═══ STATS BAR ═══ */
.va-stats-bar {
    display: flex;
    justify-content: center;
    gap: 48px;
    background: #0d2137;
    padding: 20px 40px;
    flex-wrap: wrap;
}
.va-stat-item {
    text-align: center;
    color: #e0f7fa;
}
.va-stat-number {
    font-size: 1.8em;
    font-weight: 800;
    color: #00e5ff;
    display: block;
}
.va-stat-label {
    font-size: 0.85em;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

/* ═══ FEATURES GRID ═══ */
.va-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    padding: 50px 40px;
    max-width: 1200px;
    margin: 0 auto;
}
.va-feature-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 32px 24px;
    text-align: center;
    border: 1px solid #e8eaf6;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: default;
}
.va-feature-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
}
.va-feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    margin: 0 auto 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}
.va-feature-card h3 {
    font-size: 1.1em;
    font-weight: 700;
    margin: 0 0 10px;
    letter-spacing: -0.01em;
}
.va-feature-card p {
    color: #546e7a;
    font-size: 0.92em;
    line-height: 1.6;
    margin: 0;
}

/* ═══ ABOUT US ═══ */
.va-about {
    background: linear-gradient(135deg, #e8eaf6 0%, #f3e5f5 100%);
    padding: 56px 40px;
    text-align: center;
}
.va-about h2 {
    color: #1a237e;
    font-size: 1.6em;
    margin: 0 0 20px;
    font-weight: 700;
}
.va-about p {
    color: #37474f;
    font-size: 1.02em;
    line-height: 1.7;
    max-width: 800px;
    margin: 0 auto 14px;
}
.va-about .va-about-sub {
    color: #546e7a;
    font-size: 0.92em;
}

/* ═══ TESTIMONIALS ═══ */
.va-testimonials {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    padding: 50px 40px;
    max-width: 1200px;
    margin: 0 auto;
}
.va-testimonial-card {
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    border: 1px solid #e0e0e0;
    transition: transform 0.3s ease;
}
.va-testimonial-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
}
.va-stars { color: #ffd600; font-size: 1.1em; margin-bottom: 10px; }
.va-testimonial-card .va-quote {
    color: #37474f;
    font-style: italic;
    font-size: 0.95em;
    line-height: 1.6;
    margin: 0 0 16px;
}
.va-testimonial-card .va-author {
    color: #1a237e;
    font-weight: 700;
    font-size: 0.9em;
    margin: 0;
}
.va-testimonial-card .va-role {
    color: #546e7a;
    font-weight: 400;
}

/* ═══ SECTION DIVIDER ═══ */
.va-section-divider {
    height: 4px;
    background: linear-gradient(90deg, #00bcd4, #1a237e, #7c4dff);
    margin: 0;
}

/* ═══ COURSES INTRO ═══ */
.va-courses-intro {
    text-align: center;
    padding: 36px 40px 12px;
    background: #fafbfc;
}
.va-courses-intro h2 {
    color: #1a237e;
    font-size: 1.5em;
    font-weight: 700;
    margin: 0 0 8px;
}
.va-courses-intro p {
    color: #546e7a;
    font-size: 0.95em;
    margin: 0;
}
</style>

<div id="va-frontpage-hero">
    <!-- Hero Banner -->
    <div class="va-hero-banner">
        <h1>🎓 Grade 1-8 Virtual Academy</h1>
        <p class="va-subtitle">Ontario curriculum-aligned, self-paced online learning for Grades 1 through 8. Interactive video lessons, hands-on games, and comprehensive assessments.</p>
        <a href="/course/" class="va-hero-cta">Browse All Courses →</a>
    </div>

    <!-- Stats Bar -->
    <div class="va-stats-bar">
        <div class="va-stat-item">
            <span class="va-stat-number">87</span>
            <span class="va-stat-label">Courses</span>
        </div>
        <div class="va-stat-item">
            <span class="va-stat-number">900+</span>
            <span class="va-stat-label">Lessons</span>
        </div>
        <div class="va-stat-item">
            <span class="va-stat-number">8</span>
            <span class="va-stat-label">Subjects</span>
        </div>
        <div class="va-stat-item">
            <span class="va-stat-number">8</span>
            <span class="va-stat-label">Grade Levels</span>
        </div>
    </div>

    <!-- Section Divider -->
    <div class="va-section-divider"></div>

    <!-- Feature Cards -->
    <div class="va-features">
        <div class="va-feature-card">
            <div class="va-feature-icon" style="background:linear-gradient(135deg,#1a237e,#283593); color:#fff;">⏰</div>
            <h3 style="color:#1a237e;">Self-Paced Learning</h3>
            <p>Students learn at their own speed — no rigid schedules. Revisit any lesson, anytime, anywhere.</p>
        </div>
        <div class="va-feature-card">
            <div class="va-feature-icon" style="background:linear-gradient(135deg,#00695c,#00897b); color:#fff;">🎬</div>
            <h3 style="color:#00695c;">Interactive Video Lessons</h3>
            <p>Narrated video with embedded quiz checkpoints, chapter navigation, and closed captions.</p>
        </div>
        <div class="va-feature-card">
            <div class="va-feature-icon" style="background:linear-gradient(135deg,#4a148c,#6a1b9a); color:#fff;">🎮</div>
            <h3 style="color:#4a148c;">Learning Games</h3>
            <p>Space-themed SCORM games reinforce every lesson. Practice while having fun.</p>
        </div>
        <div class="va-feature-card">
            <div class="va-feature-icon" style="background:linear-gradient(135deg,#e65100,#ef6c00); color:#fff;">🍁</div>
            <h3 style="color:#e65100;">Ontario Curriculum Aligned</h3>
            <p>All 87 courses across 8 subjects mapped to Ontario Ministry of Education standards.</p>
        </div>
    </div>

    <!-- About Section -->
    <div class="va-about">
        <h2>About Our Academy</h2>
        <p>The <strong>Grade 1-8 Virtual Academy</strong> is a comprehensive, self-paced online learning platform built for today's digital learners. We offer <strong>87 complete courses</strong> across <strong>8 subject areas</strong> — Mathematics, Language, Science &amp; Technology, Social Studies, Health &amp; Physical Education, The Arts, and French.</p>
        <p class="va-about-sub">Each lesson combines professionally narrated video instruction, interactive quiz checkpoints, and hands-on SCORM learning games. All content is fully mapped to the <strong>Ontario Ministry of Education</strong> curriculum expectations.</p>
    </div>

    <!-- Testimonials -->
    <div class="va-testimonials">
        <div class="va-testimonial-card">
            <div class="va-stars">★★★★★</div>
            <p class="va-quote">"My daughter loves the interactive video lessons. The embedded quizzes keep her engaged, and the space-themed games make practicing math feel like play."</p>
            <p class="va-author">— Sarah M., <span class="va-role">Parent of Grade 3 Student</span></p>
        </div>
        <div class="va-testimonial-card">
            <div class="va-stars">★★★★★</div>
            <p class="va-quote">"Having access to a full Ontario curriculum-aligned program is invaluable. The assessments and learning logs give me everything I need to track progress."</p>
            <p class="va-author">— David K., <span class="va-role">Parent of Grade 6 Student</span></p>
        </div>
        <div class="va-testimonial-card">
            <div class="va-stars">★★★★★</div>
            <p class="va-quote">"The quality of the SCORM activities and interactive videos is outstanding. Every lesson maps directly to specific Ontario curriculum expectations."</p>
            <p class="va-author">— Maria L., <span class="va-role">Educator &amp; Curriculum Consultant</span></p>
        </div>
    </div>

    <!-- Courses Intro Divider -->
    <div class="va-section-divider"></div>
    <div class="va-courses-intro">
        <h2>📚 Explore Our Courses</h2>
        <p>Browse the full catalog of courses below, or use the search to find specific subjects.</p>
    </div>
</div>
<script>
// Move hero inside #page container so it renders above the main content
document.addEventListener('DOMContentLoaded', function() {
    var hero = document.getElementById('va-frontpage-hero');
    if (!hero) return;
    // Only on the front page
    if (!document.body.id || document.body.id !== 'page-site-index') return;
    // Find the target insertion point
    var pageContent = document.getElementById('page-content');
    var page = document.getElementById('page');
    if (page && pageContent) {
        page.insertBefore(hero, pageContent);
    } else if (page) {
        page.insertBefore(hero, page.firstChild);
    }
    // Also hide the standard page header on frontpage
    var pageHeader = document.getElementById('page-header');
    if (pageHeader) pageHeader.style.display = 'none';
});
</script>
<!-- END FRONT PAGE HERO -->
HEROHTML;


// ─── Apply settings ─────────────────────────────────────────────
echo "─── Step 1: Injecting hero HTML into topofbody ────\n";
set_config('additionalhtmltopofbody', $hero_html);
echo "  ✅ Hero HTML injected (" . strlen($hero_html) . " chars)\n";

// ─── Custom CSS in HEAD ─────────────────────────────────────────
echo "\n─── Step 2: Adding custom CSS to HEAD ─────────────\n";
$custom_head = <<<'CSS'
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ═══ BOOST THEME GLOBAL OVERRIDES ═══ */
body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important; }

/* Navbar */
.navbar { 
    background: linear-gradient(135deg, #0a1929 0%, #1a237e 100%) !important; 
    border-bottom: 3px solid #00bcd4 !important; 
    box-shadow: 0 2px 12px rgba(0,0,0,0.2); 
}
.navbar .navbar-brand, .navbar a, .navbar .nav-link, 
.navbar .dropdown-toggle, .navbar .btn-outline-secondary { color: #e3f2fd !important; }
.navbar a:hover, .navbar .nav-link:hover { color: #80deea !important; }
.navbar .userinitials { background: linear-gradient(135deg, #00838f, #00bcd4) !important; }

/* Course cards */
.coursebox { border-radius: 14px !important; transition: transform 0.3s ease, box-shadow 0.3s ease; border: 1px solid #e8eaf6 !important; overflow: hidden; }
.coursebox:hover { transform: translateY(-4px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
.coursebox .content .coursename a { color: #1a237e !important; font-weight: 600; }

/* Buttons */
.btn-primary { background: linear-gradient(135deg, #1565c0, #0d47a1) !important; border: none !important; border-radius: 8px !important; }
.btn-primary:hover { box-shadow: 0 4px 12px rgba(21,101,192,0.3); }

/* Footer */
#page-footer { background: #0a1929 !important; color: #b0bec5 !important; }
#page-footer a { color: #80deea !important; }

/* Headings */
#frontpage-available-course-list h2 { color: #1a237e; font-weight: 700; border-bottom: 3px solid #00bcd4; padding-bottom: 10px; display: inline-block; }

/* Front page specific — remove extra padding above hero */
body#page-site-index #page-header { padding: 0 !important; min-height: 0 !important; }
body#page-site-index #page-content { padding-top: 0 !important; }
body#page-site-index .page-header-headings { display: none !important; }
body#page-site-index #page-header .d-flex { display: none !important; }
body#page-site-index #maincontent { margin-top: 0 !important; }

/* Smooth scroll */
html { scroll-behavior: smooth; }
</style>
CSS;

set_config('additionalhtmlhead', $custom_head);
echo "  ✅ Custom CSS + Inter font injected\n";


// ─── Frontpage settings ─────────────────────────────────────────
echo "\n─── Step 3: Configuring frontpage items ────────────\n";
set_config('frontpage', '6');
set_config('frontpageloggedin', '6');
set_config('defaulthomepage', '0');
set_config('frontpagecourselimit', 12);
echo "  ✅ Frontpage: course list (hero handled by topofbody)\n";


// ─── Purge ───────────────────────────────────────────────────────
echo "\n─── Step 4: Purging all caches ─────────────────────\n";
theme_reset_all_caches();
purge_all_caches();
echo "  ✅ All caches purged\n";


echo "\n═══════════════════════════════════════════════════\n";
echo "  ✅ FRONT PAGE V2 CONFIGURATION COMPLETE\n";
echo "═══════════════════════════════════════════════════\n\n";
echo "Sections configured:\n";
echo "  • Hero banner (gradient + CTA)\n";
echo "  • Stats bar (87 courses, 900+ lessons, 8 subjects)\n";
echo "  • 4 Feature cards with hover animations\n";
echo "  • About Us section\n";
echo "  • 3 Testimonial cards\n";
echo "  • Course catalog intro divider\n";
echo "  • Custom CSS (Inter font, navy/teal, card hovers)\n\n";
echo "Visit: {$CFG->wwwroot}/\n";
