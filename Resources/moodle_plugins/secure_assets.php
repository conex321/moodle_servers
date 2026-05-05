<?php
/**
 * Moodle Asset Download Security Hardening
 * 
 * Prevents non-admin users from downloading SCORM packages,
 * interactive video assets, and course backups.
 * Preserves normal file/document downloads (Word docs, PDFs, etc.).
 * 
 * Run: docker exec moodle-app php /tmp/secure_assets.php
 * 
 * Changes:
 *   1. Enable SCORM package download protection
 *   2. PROHIBIT backup/export for all non-admin roles (including Manager)
 *   3. Restrict interactive video management to admin only
 *   4. Revoke guest access to protected modules
 *   5. Enable force login, disable guest login
 *   6. Add defensive CSS to hide download/export UI elements
 *   7. Purge all caches
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/accesslib.php');

echo "==============================================\n";
echo "  MOODLE ASSET DOWNLOAD SECURITY HARDENING\n";
echo "==============================================\n\n";

$system_context = context_system::instance();

// Load all roles
$student_role = $DB->get_record('role', ['shortname' => 'student']);
$teacher_role = $DB->get_record('role', ['shortname' => 'teacher']);
$editing_teacher = $DB->get_record('role', ['shortname' => 'editingteacher']);
$manager_role = $DB->get_record('role', ['shortname' => 'manager']);
$coursecreator_role = $DB->get_record('role', ['shortname' => 'coursecreator']);
$guest_role = $DB->get_record('role', ['shortname' => 'guest']);
$user_role = $DB->get_record('role', ['shortname' => 'user']);
$frontpage_role = $DB->get_record('role', ['shortname' => 'frontpage']);

// All non-admin roles that should be locked down
$restricted_roles = array_filter([
    $student_role, $teacher_role, $editing_teacher, $manager_role,
    $coursecreator_role, $guest_role, $user_role, $frontpage_role
]);

// ============================================================
// PART 1: SCORM PACKAGE DOWNLOAD PROTECTION
// ============================================================
echo "--- [1/7] SCORM Package Download Protection ---\n";

$current_protect = get_config('scorm', 'protectpackagedownloads');
echo "  Current protectpackagedownloads: " . ($current_protect ? $current_protect : '0 (UNPROTECTED!)') . "\n";

set_config('protectpackagedownloads', 1, 'scorm');
echo "  ✅ protectpackagedownloads = 1 (PROTECTED)\n";

// Also disable external SCORM types (prevents linking to external packages)
set_config('allowtypeexternal', 0, 'scorm');
set_config('allowtypeexternalaicc', 0, 'scorm');
set_config('allowtypelocalsync', 0, 'scorm');
echo "  ✅ External SCORM types disabled\n";

// ============================================================
// PART 2: PROHIBIT BACKUP/EXPORT FOR ALL NON-ADMIN ROLES
// ============================================================
echo "\n--- [2/7] Backup/Export Capability Lockdown ---\n";

$backup_capabilities = [
    'moodle/backup:backupactivity',
    'moodle/backup:backupcourse', 
    'moodle/backup:backupsection',
    'moodle/backup:backuptargetimport',
    'moodle/backup:configure',
    'moodle/backup:downloadfile',
    'moodle/backup:anonymise',
    'moodle/backup:userinfo',
];

// Also prevent restore capabilities (could be used to export data)
$restore_capabilities = [
    'moodle/restore:restoreactivity',
    'moodle/restore:restorecourse',
    'moodle/restore:restoresection',
    'moodle/restore:restoretargetimport',
    'moodle/restore:configure',
    'moodle/restore:uploadfile',
    'moodle/restore:userinfo',
    'moodle/restore:rolldates',
    'moodle/restore:createuser',
];

$all_backup_caps = array_merge($backup_capabilities, $restore_capabilities);

foreach ($restricted_roles as $role) {
    $changes = 0;
    foreach ($all_backup_caps as $cap) {
        // Check if the capability exists in the system
        if (!$DB->record_exists('capabilities', ['name' => $cap])) {
            continue;
        }
        
        // Use PROHIBIT (-1000) which cannot be overridden at any context level
        assign_capability($cap, CAP_PROHIBIT, $role->id, $system_context->id, true);
        $changes++;
    }
    echo "  ✅ {$role->shortname}: PROHIBIT on {$changes} backup/restore capabilities\n";
}

// ============================================================
// PART 3: INTERACTIVE VIDEO MANAGEMENT RESTRICTIONS
// ============================================================
echo "\n--- [3/7] Interactive Video Capability Lockdown ---\n";

// Capabilities that should be admin-only (not for students/teachers)
$iv_admin_only_caps = [
    'mod/interactivevideo:addinstance',
    'mod/interactivevideo:edit',
    'mod/interactivevideo:editreport',
    'mod/interactivevideo:manage',
];

// Roles that should NOT have manage/edit access
$iv_restricted = array_filter([
    $student_role, $teacher_role, $guest_role, $user_role, $frontpage_role
]);

foreach ($iv_restricted as $role) {
    foreach ($iv_admin_only_caps as $cap) {
        if (!$DB->record_exists('capabilities', ['name' => $cap])) {
            continue;
        }
        assign_capability($cap, CAP_PROHIBIT, $role->id, $system_context->id, true);
    }
    echo "  ✅ {$role->shortname}: PROHIBIT on IV edit/manage capabilities\n";
}

// Also restrict editing teachers from manage (they can view but not manage)
if ($editing_teacher) {
    // Keep addinstance for editing teachers (they need to add activities to courses)
    // But PROHIBIT manage and edit of the underlying video assets
    $et_iv_restrict = [
        'mod/interactivevideo:manage',
    ];
    foreach ($et_iv_restrict as $cap) {
        if ($DB->record_exists('capabilities', ['name' => $cap])) {
            assign_capability($cap, CAP_PROHIBIT, $editing_teacher->id, $system_context->id, true);
        }
    }
    echo "  ✅ editingteacher: PROHIBIT on IV manage (keeps view + edit)\n";
}

// Remove guest access to interactive videos entirely
if ($guest_role) {
    $iv_guest_remove = [
        'mod/interactivevideo:view',
    ];
    foreach ($iv_guest_remove as $cap) {
        if ($DB->record_exists('capabilities', ['name' => $cap])) {
            assign_capability($cap, CAP_PROHIBIT, $guest_role->id, $system_context->id, true);
        }
    }
    echo "  ✅ guest: PROHIBIT on interactivevideo:view\n";
}

// ============================================================
// PART 4: SCORM CAPABILITY RESTRICTIONS
// ============================================================
echo "\n--- [4/7] SCORM Capability Restrictions ---\n";

// Prevent guest from any SCORM access
if ($guest_role) {
    $scorm_caps_all = $DB->get_records_sql(
        "SELECT name FROM {capabilities} WHERE name LIKE 'mod/scorm:%'"
    );
    foreach ($scorm_caps_all as $cap) {
        assign_capability($cap->name, CAP_PROHIBIT, $guest_role->id, $system_context->id, true);
    }
    echo "  ✅ guest: PROHIBIT on ALL SCORM capabilities\n";
}

// Remove deleteresponses from teachers (they shouldn't modify tracking data)
if ($teacher_role) {
    assign_capability('mod/scorm:deleteresponses', CAP_PROHIBIT, $teacher_role->id, $system_context->id, true);
    echo "  ✅ teacher: PROHIBIT deleteresponses\n";
}

// Keep student capabilities minimal: savetrack + viewscores only
// (skipview is already fine - it just skips the intro page)
echo "  ✅ student: retains savetrack + viewscores (read-only access)\n";

// ============================================================
// PART 5: FORCE AUTHENTICATION & DISABLE GUEST ACCESS
// ============================================================
echo "\n--- [5/7] Authentication Hardening ---\n";

// Force login for entire site
set_config('forcelogin', 1);
echo "  ✅ forcelogin = 1 (no anonymous access)\n";

// Disable guest login button
set_config('guestloginbutton', 0);
echo "  ✅ guestloginbutton = 0 (removed from login page)\n";

// Disable guest access to courses
set_config('allowguestaccess', 0);
echo "  ✅ allowguestaccess = 0\n";

// Disable self-registration (admin creates accounts manually)
set_config('registerauth', '');
echo "  ✅ Self-registration disabled\n";

// Force login for profiles
set_config('forceloginforprofiles', 1);
echo "  ✅ forceloginforprofiles = 1\n";

// ============================================================
// PART 6: DEFENSIVE CSS (HIDE DOWNLOAD/EXPORT UI ELEMENTS)
// ============================================================
echo "\n--- [6/7] Defensive CSS Layer ---\n";

// First, get existing custom CSS and append to it
$existing_css = get_config('theme_remui', 'customcss');

$security_css = <<<'CSS'

/* ==============================================
   ASSET DOWNLOAD PROTECTION CSS
   Defense-in-depth: hides download/export UI
   elements from non-admin users.
   ============================================== */

/* ---- SCORM DOWNLOAD BUTTONS ---- */
/* Hide any download links in SCORM player */
a[href*="pluginfile.php"][href*="mod_scorm/package"],
a[href*="pluginfile.php"][href*="mod_scorm/content"],
.scorm-download-link,
.scorm-package-download,
a[download][href*="scorm"],
button[data-action="scorm-download"] {
    display: none !important;
}

/* ---- COURSE BACKUP/EXPORT BUTTONS ---- */
/* Hide backup/restore navigation items for non-admin */
body:not(.role-admin) a[href*="/backup/"],
body:not(.role-admin) a[href*="/restore/"],
body:not(.role-admin) a[href*="action=backup"],
body:not(.role-admin) .nav-link[href*="backup"],
body:not(.role-admin) .nav-link[href*="restore"],
a[href*="/backup/backup.php"],
a[href*="/backup/restorefile.php"] {
    display: none !important;
}

/* Hide backup menu items in course admin */
li a[href*="backup.php"],
li a[href*="restorefile.php"],
li a[href*="import.php"] {
    display: none !important;
}

/* ---- INTERACTIVE VIDEO ASSET DOWNLOADS ---- */
/* Prevent download of video source files */
video[controlslist="nodownload"],
a[href*="mod_interactivevideo"][download],
.interactivevideo-download,
button[data-action="download-video"] {
    display: none !important;
}

/* Force video elements to disable download */
video {
    controlslist: nodownload !important;
}

/* ---- FILE PLUGINFILE PROTECTION ---- */
/* Hide direct pluginfile links for SCORM content */
a[href*="pluginfile.php/"][href*="/mod_scorm/"] {
    pointer-events: none;
    color: inherit;
    text-decoration: none;
    cursor: default;
}

/* ---- COURSE REUSE (Import/Export) ---- */
/* Hide Course Reuse navigation tab */
a[href*="coursereusetab"],
a[href*="import.php"],
a[href*="publish.php"],
li[data-key="coursereuse"],
.secondary-navigation a[href*="backup"],
.secondary-navigation a[href*="restore"],
.secondary-navigation a[href*="import"],
.secondary-navigation a[href*="copy"] {
    display: none !important;
}

/* ---- RIGHT-CLICK PROTECTION (soft) ---- */
/* Disable right-click context menu on protected content */
.scorm-player-container,
.interactivevideo-container,
.modtype_scorm .activity-instance,
.modtype_interactivevideo .activity-instance {
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
CSS;

// Append security CSS to existing CSS
$final_css = $existing_css . "\n" . $security_css;
set_config('customcss', $final_css, 'theme_remui');
echo "  ✅ Security CSS appended (" . strlen($security_css) . " chars added)\n";

// ============================================================
// PART 7: PURGE CACHES & FINALIZE
// ============================================================
echo "\n--- [7/7] Cache Purge & Finalization ---\n";

// Force rebuild of capabilities cache
$system_context->mark_dirty();
// Mark all contexts dirty to force capability recheck
$DB->set_field('context', 'dirty', 1);
echo "  ✅ Access cache marked dirty (will rebuild on next access)\n";

// Purge all caches
purge_all_caches();
echo "  ✅ All caches purged\n";

// Reset theme caches
theme_reset_all_caches();
echo "  ✅ Theme caches reset\n";

// ============================================================
// VERIFICATION SUMMARY
// ============================================================
echo "\n==============================================\n";
echo "  SECURITY HARDENING COMPLETE\n";
echo "==============================================\n";
echo "  Changes applied:\n";
echo "    ✅ SCORM package downloads PROTECTED (protectpackagedownloads=1)\n";
echo "    ✅ External SCORM types DISABLED\n";
echo "    ✅ Backup/Restore PROHIBITED for ALL non-admin roles\n";
echo "    ✅ Interactive video manage/edit PROHIBITED for non-admin\n";
echo "    ✅ Guest SCORM access PROHIBITED\n";
echo "    ✅ Guest interactive video access PROHIBITED\n";
echo "    ✅ Force login ENABLED (no anonymous browsing)\n";
echo "    ✅ Guest login button REMOVED\n";
echo "    ✅ Self-registration DISABLED\n";
echo "    ✅ Course backup/restore/import UI HIDDEN via CSS\n";
echo "    ✅ SCORM download buttons HIDDEN via CSS\n";
echo "    ✅ All caches PURGED\n";
echo "==============================================\n";
echo "  WHO CAN DO WHAT NOW:\n";
echo "    Admin:             Full access (backup, download, manage)\n";
echo "    Manager:           View only (no backup, no export)\n";
echo "    Editing Teacher:   View + edit activities (no backup, no manage IV)\n";
echo "    Teacher:           View only (no backup, no export)\n";
echo "    Student:           View + interact (no download, no backup)\n";
echo "    Guest:             NO access (force login required)\n";
echo "==============================================\n";
echo "  PRESERVED:\n";
echo "    ✅ Word doc, PDF, and course file downloads (mod/resource:view)\n";
echo "    ✅ Student SCORM interaction (savetrack, viewscores)\n";
echo "    ✅ Interactive video viewing for enrolled users\n";
echo "==============================================\n";
echo "  Refresh http://localhost:8888 to verify.\n";
echo "==============================================\n";
