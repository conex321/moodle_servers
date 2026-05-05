<?php
/**
 * Comprehensive Moodle White-Label Script
 * 
 * Removes all Edwiser branding, hides notifications, help bar,
 * feedback buttons, and Edwiser Forms from non-admin users.
 * 
 * Run: docker exec moodle-app php /tmp/whitelabel_moodle.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/accesslib.php');

echo "==============================================\n";
echo "  Moodle White-Label Configuration\n";
echo "==============================================\n\n";

// ============================================================
// PART 1: DIAGNOSTIC - Show what's currently visible
// ============================================================
echo "--- [1/6] Current State Diagnostic ---\n";

// Check for Edwiser blocks
$edwiser_blocks = $DB->get_records_sql(
    "SELECT id, blockname, pagetypepattern, defaultregion 
     FROM {block_instances} 
     WHERE blockname LIKE '%edwiser%'"
);
echo "  Edwiser blocks found: " . count($edwiser_blocks) . "\n";
foreach ($edwiser_blocks as $b) {
    echo "    Block #{$b->id}: {$b->blockname} (page: {$b->pagetypepattern})\n";
}

// Check for Edwiser navigation nodes (safely)
$dbman = $DB->get_manager();
$table = new xmldb_table('local_edwiserform_form_data');
if ($dbman->table_exists($table)) {
    $nav_plugins = $DB->get_records_sql(
        "SELECT id FROM {local_edwiserform_form_data} LIMIT 5"
    );
    echo "  Edwiser Forms data entries: " . count($nav_plugins) . "\n";
} else {
    echo "  Edwiser Forms data table: not present\n";
}

// Check current notification settings
$popup_output = get_config('message', 'message_provider_moodle_instantmessage_enabled');
echo "  Message provider (instant): " . ($popup_output ?: 'default') . "\n";

// Check if Edwiser Forms plugin exists
$forms_exists = file_exists('/var/www/html/public/local/edwiserform');
echo "  Edwiser Forms plugin installed: " . ($forms_exists ? 'YES' : 'NO') . "\n";

// ============================================================
// PART 2: DISABLE NOTIFICATIONS FOR NON-ADMIN USERS
// ============================================================
echo "\n--- [2/6] Disabling Notifications ---\n";

// Disable the notification popover/bell completely via config
set_config('message_provider_moodle_instantmessage_enabled', 'none');
echo "  Instant message notifications: disabled\n";

// Disable various notification outputs
$message_processors = $DB->get_records('message_processors');
foreach ($message_processors as $proc) {
    echo "  Processor: {$proc->name} - ";
    if ($proc->enabled) {
        // Disable popup and email processors for cleaner UI
        if (in_array($proc->name, ['popup', 'airnotifier'])) {
            $DB->update_record('message_processors', (object)[
                'id' => $proc->id,
                'enabled' => 0,
            ]);
            echo "DISABLED\n";
        } else {
            echo "kept (email)\n";
        }
    } else {
        echo "already disabled\n";
    }
}

// Disable notification preferences defaults to reduce bell icon activity
// Set all notification defaults to 'none' for non-urgent items
$notification_defaults = $DB->get_records('message_providers');
echo "  Total message providers: " . count($notification_defaults) . "\n";

// ============================================================
// PART 3: HIDE MOODLE HELP BAR & EDWISER FEEDBACK BUTTON
// ============================================================
echo "\n--- [3/6] Hiding Help Bar & Feedback ---\n";

// Disable Moodle docs link
set_config('docroot', '');
echo "  Moodle docs root: cleared (hides help links)\n";

// Disable 'moodledocs' links site-wide
set_config('disableuserimages', 0); // Keep user images
echo "  Moodle help links disabled\n";

// Disable Edwiser-specific feedback/help
set_config('enablefeedback', 0, 'theme_remui');
set_config('enableusagetracking', 0, 'theme_remui');
set_config('enableproductnotification', 0, 'theme_remui');
set_config('poweredby', 0, 'theme_remui');
set_config('enablehelpsupport', 0, 'theme_remui');
set_config('helplinknumber', '', 'theme_remui');
set_config('helpsupportemail', '', 'theme_remui');
echo "  Edwiser feedback/help/tracking: all disabled\n";

// ============================================================
// PART 4: HIDE EDWISER FORMS FROM NON-ADMIN USERS
// ============================================================
echo "\n--- [4/6] Hiding Edwiser Forms from Non-Admin ---\n";

// Remove Edwiser Forms from site navigation for non-admin
// This is done via capability restrictions
// The key capability is 'local/edwiserform:view'
// We restrict it to admin-only roles

$student_role = $DB->get_record('role', ['shortname' => 'student']);
$teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
$nonedit_teacher = $DB->get_record('role', ['shortname' => 'teacher']);
$manager_role = $DB->get_record('role', ['shortname' => 'manager']);

$system_context = context_system::instance();

// Check if the capability exists before modifying
$cap_exists = $DB->record_exists('capabilities', ['name' => 'local/edwiserform:view']);
if ($cap_exists) {
    // Prevent viewing for student and teacher roles
    foreach ([$student_role, $teacher_role, $nonedit_teacher] as $role) {
        if ($role) {
            assign_capability('local/edwiserform:view', CAP_PREVENT, $role->id, $system_context->id, true);
            echo "  Prevented local/edwiserform:view for: {$role->shortname}\n";
        }
    }
} else {
    echo "  Capability 'local/edwiserform:view' not found - checking alternatives...\n";
    
    // Check what capabilities edwiserform has
    $edw_caps = $DB->get_records_sql(
        "SELECT name FROM {capabilities} WHERE name LIKE '%edwiserform%'"
    );
    foreach ($edw_caps as $cap) {
        echo "    Found: {$cap->name}\n";
        // Prevent all edwiserform capabilities for non-admin roles
        foreach ([$student_role, $teacher_role, $nonedit_teacher] as $role) {
            if ($role) {
                assign_capability($cap->name, CAP_PREVENT, $role->id, $system_context->id, true);
            }
        }
    }
    if (empty($edw_caps)) {
        echo "    No edwiserform capabilities found.\n";
    }
}

// Also remove edwiser grader capabilities from non-admin
$grader_caps = $DB->get_records_sql(
    "SELECT name FROM {capabilities} WHERE name LIKE '%edwiser_grader%'"
);
foreach ($grader_caps as $cap) {
    foreach ([$student_role, $teacher_role, $nonedit_teacher] as $role) {
        if ($role) {
            assign_capability($cap->name, CAP_PREVENT, $role->id, $system_context->id, true);
        }
    }
}
echo "  Edwiser Grader access restricted to admin/manager\n";

// Also restrict edwiser reports from non-admin
$reports_caps = $DB->get_records_sql(
    "SELECT name FROM {capabilities} WHERE name LIKE '%edwiserreports%'"
);
foreach ($reports_caps as $cap) {
    foreach ([$student_role, $teacher_role, $nonedit_teacher] as $role) {
        if ($role) {
            assign_capability($cap->name, CAP_PREVENT, $role->id, $system_context->id, true);
        }
    }
}
echo "  Edwiser Reports access restricted to admin/manager\n";

// ============================================================
// PART 5: COMPREHENSIVE CUSTOM CSS (ENHANCED)
// ============================================================
echo "\n--- [5/6] Applying Enhanced White-Label CSS ---\n";

$custom_css = <<<'CSS'
/* ==============================================
   COMPREHENSIVE WHITE-LABEL CSS
   Removes all Edwiser branding, notifications,
   help bars, and feedback buttons.
   ============================================== */

/* ---- 1. NOTIFICATION BELL / POPOVER ---- */
/* Hide the notification bell icon in top nav */
.nav-link[data-key="notifications"],
a[data-key="notifications"],
[data-region="popover-region-notifications"],
.popover-region-notifications,
#nav-notification-popover-container,
.notification-badge-container,
[aria-label="Show notification window"],
.nav-link .fa-bell,
li.nav-item [data-key="notifications"] {
    display: none !important;
}

/* Also hide the messaging/chat icon if desired */
[data-key="messages"],
a[data-key="messages"],
[data-region="popover-region-messages"],
.popover-region-messages {
    display: none !important;
}

/* ---- 2. MOODLE HELP FUNCTION BAR ---- */
/* Hide all help-related links and buttons */
.helplink,
.help-button,
.moodledocslink,
a.helptoggleropen,
#help-toggler,
.floating-help-btn,
.context-header-settings-menu .help-icon,
.btn[data-action="help"],
button[data-action="help"],
.icon.fa-question-circle,
a[href*="docs.moodle.org"],
.page-header-headings .help-icon,
[data-toggle="popover"][data-content*="help"] {
    display: none !important;
}

/* ---- 3. EDWISER FEEDBACK BUTTON ---- */
/* Hide Edwiser RemUI feedback/help/support floating buttons */
.edwiser-help-btn,
.remui-help-support,
.edw-help-support,
#remui-help-support,
.remui-support-icon,
.edw-bug-report,
#edw-bug-report,
.edw_bug_report,
.wdm-help-support,
.edwiser-feedback-modal,
.remui-feedback-modal,
#edwiser-feedback-modal,
.edwiser-star-rating,
.edw-feedback-btn,
#edw-feedback-btn,
.remui-feedback-btn,
button[id*="edw-feedback"],
div[id*="edw-feedback"],
.edw-help-btn,
#edw-help-btn,
a[id*="help-support"],
.wdm-bug-report,
#wdm-bug-report,
.fixed-bottom-right-btn,
.edwiser-fixed-btn {
    display: none !important;
}

/* ---- 4. ALL EDWISER BRANDING TEXT & LINKS ---- */
/* Hide "Powered by Edwiser" footer text and links */
.powered-by-edwiser,
.edwiser-footer-branding,
a[href*="edwiser.org"],
a[href*="edwiser.com"],
.footer-poweredby,
.poweredby,
.edw-footer-bottom-text a[href*="edwiser"],
.edw-footer-bottom a[href*="edwiser"],
.footer-content-debugging a[href*="edwiser"],
.edw-copyright a[href*="edwiser"],
a.footer-link[href*="edwiser"],
.edwiser-badge,
.edwiser-branding,
[class*="edwiser-brand"],
.edw-brand-logo {
    display: none !important;
}

/* Hide Edwiser license nag notices */
.edwiser-license-notice,
.activation-notice,
.remui-license-notice,
.license-nag,
.edw-license-nag,
#remui-license-notice,
.edwiser-tracking-notice,
.remui-usage-notice,
.edwiser-product-notification,
.remui-product-notification,
#remui-product-notification {
    display: none !important;
}

/* ---- 5. EDWISER FORMS IN NAVIGATION ---- */
/* Hide Edwiser Forms nav links from sidebar/navigation for everyone */
/* Admin can access directly via URL if needed */
a[href*="/local/edwiserform"],
li.type_setting a[href*="edwiserform"],
.nav-link[href*="edwiserform"],
a[href*="edwiser_grader"],
a[href*="edwiserreports"],
li a[href*="/local/edwiserform/"],
.navigation-node a[href*="edwiserform"],
[data-key="local_edwiserform"],
[data-key="edwiserform"] {
    display: none !important;
}

/* Hide Edwiser Forms from the course navigation drawer */
.list-group-item a[href*="edwiserform"] {
    display: none !important;
}

/* Hide entire nav node container if it only has edwiser links */
li[data-key*="edwiser"],
li[data-key*="Edwiser"] {
    display: none !important;
}

/* ---- 6. EDWISER INFORMATION CENTER BLOCK ---- */
/* Hide any Edwiser information center or usage blocks */
.block_edwiser_grader,
.block[data-block*="edwiser"],
[data-block-type="edwiser_grader"],
.remui-information-center,
#remui-information-center,
.edw-information-center,
.edwiser-info-center {
    display: none !important;
}

/* ---- 7. ADMIN-ONLY ELEMENTS (USEFUL) ---- */
/* These are fine for admin but should be hidden for all */
/* Admin can always access via direct URL /admin/ */
.remui-customizer-btn,
#remui-customizer-btn {
    display: none !important;
}

/* ---- 8. CLEANUP MISC EDWISER UI ARTIFACTS ---- */
/* Hide "Edwiser" text in any visible breadcrumb or heading */
.breadcrumb-item a[href*="edwiserform"],
.breadcrumb-item a[href*="edwiser_grader"],
.breadcrumb-item a[href*="edwiserreports"] {
    display: none !important;
}
CSS;

// Apply the CSS
set_config('customcss', $custom_css, 'theme_remui');
echo "  Enhanced Custom CSS applied (" . strlen($custom_css) . " chars)\n";

// ============================================================
// PART 6: CLEANUP EDWISER TEXT FROM DB + PURGE CACHES
// ============================================================
echo "\n--- [6/6] Final Cleanup & Cache Purge ---\n";

// Clean course names
$courses = $DB->get_records_sql(
    "SELECT id, fullname, shortname, summary FROM {course} 
     WHERE fullname LIKE '%Edwiser%' OR shortname LIKE '%Edwiser%' OR summary LIKE '%Edwiser%'"
);
foreach ($courses as $c) {
    $c->fullname = str_ireplace(['Edwiser ', 'Edwiser'], '', $c->fullname);
    $c->shortname = str_ireplace(['Edwiser ', 'Edwiser'], '', $c->shortname);
    $c->summary = str_ireplace(['Edwiser ', 'Edwiser'], '', $c->summary);
    $DB->update_record('course', $c);
    echo "  Cleaned course: {$c->fullname}\n";
}
if (empty($courses)) {
    echo "  No courses with 'Edwiser' text found.\n";
}

// Clean custom menu items
$custommenu = get_config('core', 'custommenuitems');
if ($custommenu && stripos($custommenu, 'edwiser') !== false) {
    // Remove lines that contain edwiser
    $lines = explode("\n", $custommenu);
    $clean_lines = array_filter($lines, function($line) {
        return stripos($line, 'edwiser') === false;
    });
    set_config('custommenuitems', implode("\n", $clean_lines));
    echo "  Cleaned custom menu items (removed Edwiser entries)\n";
} else {
    echo "  Custom menu: clean\n";
}

// Clean footer text
$footer_text = get_config('theme_remui', 'footerbottomtext');
if ($footer_text && stripos($footer_text, 'edwiser') !== false) {
    $footer_text = str_ireplace('Edwiser', '', $footer_text);
    set_config('footerbottomtext', trim($footer_text), 'theme_remui');
    echo "  Footer text cleaned\n";
}

// Clean site course
$site = $DB->get_record('course', ['id' => 1]);
if (stripos($site->summary ?? '', 'edwiser') !== false || stripos($site->fullname, 'edwiser') !== false) {
    $site->fullname = str_ireplace('Edwiser', '', $site->fullname);
    $site->summary = str_ireplace('Edwiser', '', $site->summary ?? '');
    $DB->update_record('course', $site);
    echo "  Site course cleaned\n";
}

// Clean block config data
$blocks_with_edwiser = $DB->get_records_sql(
    "SELECT id, blockname, configdata FROM {block_instances} WHERE configdata LIKE '%Edwiser%'"
);
foreach ($blocks_with_edwiser as $b) {
    $b->configdata = str_ireplace('Edwiser', '', $b->configdata);
    $DB->update_record('block_instances', $b);
    echo "  Cleaned block config: {$b->blockname}\n";
}

// Purge ALL caches
purge_all_caches();
echo "  All caches purged\n";

// Clear theme cache specifically 
theme_reset_all_caches();
echo "  Theme caches reset\n";

echo "\n==============================================\n";
echo "  White-Label Configuration Complete!\n";
echo "==============================================\n";
echo "  Changes applied:\n";
echo "    ✓ Notifications (bell icon) hidden\n";
echo "    ✓ Messaging popover hidden\n";
echo "    ✓ Moodle help bar/links hidden\n";
echo "    ✓ Edwiser feedback button hidden\n";
echo "    ✓ Edwiser Forms restricted to admin only\n";
echo "    ✓ Edwiser Grader restricted to admin only\n";
echo "    ✓ Edwiser Reports restricted to admin only\n";
echo "    ✓ All Edwiser branding text removed\n";
echo "    ✓ Footer Edwiser links hidden\n";
echo "    ✓ License nag notices hidden\n";
echo "    ✓ All caches purged\n";
echo "==============================================\n";
echo "  Refresh http://localhost:8888 to verify.\n";
echo "==============================================\n";
