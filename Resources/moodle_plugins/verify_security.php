<?php
/**
 * Post-Hardening Verification Audit
 * 
 * Verifies that all security changes from secure_assets.php are active.
 * 
 * Run: docker exec moodle-app php /tmp/verify_security.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/accesslib.php');

echo "==============================================\n";
echo "  POST-HARDENING VERIFICATION AUDIT\n";
echo "==============================================\n\n";

$pass = 0;
$fail = 0;

function check($label, $condition) {
    global $pass, $fail;
    if ($condition) {
        echo "  ✅ PASS: {$label}\n";
        $pass++;
    } else {
        echo "  ❌ FAIL: {$label}\n";
        $fail++;
    }
}

// 1. SCORM package protection
echo "--- [1] SCORM Package Protection ---\n";
$protect = get_config('scorm', 'protectpackagedownloads');
check('protectpackagedownloads = 1', $protect == 1);

$ext = get_config('scorm', 'allowtypeexternal');
check('External SCORM disabled', $ext == 0);

// 2. Force login
echo "\n--- [2] Force Login ---\n";
$forcelogin_db = get_config('core', 'forcelogin');
check('forcelogin = 1 (database)', $forcelogin_db == 1);

$forcelogin_cfg = isset($CFG->forcelogin) && $CFG->forcelogin;
check('forcelogin = true (config.php)', $forcelogin_cfg);

$guestbtn = get_config('core', 'guestloginbutton');
check('guestloginbutton = 0', $guestbtn == 0);

$guestaccess = get_config('core', 'allowguestaccess');
check('allowguestaccess = 0', $guestaccess == 0);

$selfreg = get_config('core', 'registerauth');
check('Self-registration disabled', empty($selfreg));

// 3. Backup/Restore capabilities
echo "\n--- [3] Backup/Restore Capabilities ---\n";
$system_context = context_system::instance();
$key_caps = [
    'moodle/backup:backupcourse',
    'moodle/backup:downloadfile',
    'moodle/backup:backupactivity',
];
$restricted_roles = ['student', 'teacher', 'editingteacher', 'manager', 'coursecreator', 'guest'];

foreach ($restricted_roles as $rs) {
    $role = $DB->get_record('role', ['shortname' => $rs]);
    if (!$role) continue;
    
    foreach ($key_caps as $cap) {
        $rc = $DB->get_record('role_capabilities', [
            'roleid' => $role->id,
            'capability' => $cap,
            'contextid' => $system_context->id,
        ]);
        $is_prohibit = ($rc && $rc->permission == -1000);
        check("{$rs} | {$cap} = PROHIBIT", $is_prohibit);
    }
}

// 4. Interactive Video capabilities
echo "\n--- [4] Interactive Video Capabilities ---\n";
$iv_caps_check = ['mod/interactivevideo:manage'];
$iv_restricted = ['student', 'teacher', 'guest'];

foreach ($iv_restricted as $rs) {
    $role = $DB->get_record('role', ['shortname' => $rs]);
    if (!$role) continue;
    
    foreach ($iv_caps_check as $cap) {
        if (!$DB->record_exists('capabilities', ['name' => $cap])) {
            echo "  ⏭ SKIP: {$cap} not defined in system\n";
            continue;
        }
        $rc = $DB->get_record('role_capabilities', [
            'roleid' => $role->id,
            'capability' => $cap,
            'contextid' => $system_context->id,
        ]);
        $is_prohibit = ($rc && $rc->permission == -1000);
        check("{$rs} | {$cap} = PROHIBIT", $is_prohibit);
    }
}

// Guest view prohibition
$guest_role = $DB->get_record('role', ['shortname' => 'guest']);
if ($guest_role) {
    $rc = $DB->get_record('role_capabilities', [
        'roleid' => $guest_role->id,
        'capability' => 'mod/interactivevideo:view',
        'contextid' => $system_context->id,
    ]);
    $is_prohibit = ($rc && $rc->permission == -1000);
    check("guest | mod/interactivevideo:view = PROHIBIT", $is_prohibit);
}

// 5. Guest SCORM access
echo "\n--- [5] Guest SCORM Access ---\n";
if ($guest_role) {
    $scorm_caps = $DB->get_records_sql(
        "SELECT name FROM {capabilities} WHERE name LIKE 'mod/scorm:%'"
    );
    foreach ($scorm_caps as $cap) {
        $rc = $DB->get_record('role_capabilities', [
            'roleid' => $guest_role->id,
            'capability' => $cap->name,
            'contextid' => $system_context->id,
        ]);
        $is_prohibit = ($rc && $rc->permission == -1000);
        check("guest | {$cap->name} = PROHIBIT", $is_prohibit);
    }
}

// 6. Word doc / resource view preserved
echo "\n--- [6] Resource View Preserved (Word docs, etc.) ---\n";
$student_role = $DB->get_record('role', ['shortname' => 'student']);
if ($student_role) {
    // Check that resource:view and folder:view are NOT prohibited
    $resource_check = $DB->get_record('role_capabilities', [
        'roleid' => $student_role->id,
        'capability' => 'mod/resource:view',
        'contextid' => $system_context->id,
    ]);
    $not_prohibited_resource = (!$resource_check || $resource_check->permission != -1000);
    check("student | mod/resource:view NOT prohibited", $not_prohibited_resource);
    
    $folder_check = $DB->get_record('role_capabilities', [
        'roleid' => $student_role->id,
        'capability' => 'mod/folder:view',
        'contextid' => $system_context->id,
    ]);
    $not_prohibited_folder = (!$folder_check || $folder_check->permission != -1000);
    check("student | mod/folder:view NOT prohibited", $not_prohibited_folder);
}

// 7. Student SCORM interaction preserved
echo "\n--- [7] Student SCORM Interaction Preserved ---\n";
if ($student_role) {
    $rc = $DB->get_record('role_capabilities', [
        'roleid' => $student_role->id,
        'capability' => 'mod/scorm:savetrack',
        'contextid' => $system_context->id,
    ]);
    check("student | mod/scorm:savetrack = ALLOW", ($rc && $rc->permission == 1));
    
    $rc2 = $DB->get_record('role_capabilities', [
        'roleid' => $student_role->id,
        'capability' => 'mod/scorm:viewscores',
        'contextid' => $system_context->id,
    ]);
    check("student | mod/scorm:viewscores = ALLOW", ($rc2 && $rc2->permission == 1));
}

// Summary
echo "\n==============================================\n";
echo "  VERIFICATION SUMMARY\n";
echo "==============================================\n";
echo "  PASSED: {$pass}\n";
echo "  FAILED: {$fail}\n";
if ($fail == 0) {
    echo "  🛡️ ALL CHECKS PASSED - System is hardened\n";
} else {
    echo "  ⚠️ {$fail} checks failed - review above\n";
}
echo "==============================================\n";
