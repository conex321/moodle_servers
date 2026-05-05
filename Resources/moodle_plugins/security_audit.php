<?php
/**
 * Moodle Security Audit - Asset Download Protection
 * 
 * Audits current SCORM/Interactive Video download permissions,
 * role capabilities, and security settings.
 * 
 * Run: docker exec moodle-app php /tmp/security_audit.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/accesslib.php');

echo "==============================================\n";
echo "  MOODLE SECURITY AUDIT\n";
echo "  Asset Download Protection Check\n";
echo "==============================================\n\n";

// 1. List all roles
echo "--- [1] ROLES ---\n";
$roles = $DB->get_records('role', null, 'sortorder');
foreach ($roles as $r) {
    echo "  ID={$r->id} shortname={$r->shortname} name=" . ($r->name ?: '(default)') . "\n";
}

// 2. All defined SCORM capabilities
echo "\n--- [2] ALL SCORM CAPABILITIES (defined) ---\n";
$all_scorm_caps = $DB->get_records_sql(
    "SELECT name, component FROM {capabilities} WHERE name LIKE '%scorm%' ORDER BY name"
);
foreach ($all_scorm_caps as $c) {
    echo "  {$c->name} ({$c->component})\n";
}

// 3. SCORM role capability overrides
echo "\n--- [3] SCORM CAPABILITY OVERRIDES (role_capabilities) ---\n";
$scorm_overrides = $DB->get_records_sql(
    "SELECT rc.id, rc.capability, rc.permission, r.shortname as role_shortname, rc.contextid
     FROM {role_capabilities} rc
     JOIN {role} r ON r.id = rc.roleid
     WHERE rc.capability LIKE '%scorm%'
     ORDER BY r.shortname, rc.capability"
);
foreach ($scorm_overrides as $c) {
    $perm_map = [1 => 'ALLOW', -1 => 'PREVENT', -1000 => 'PROHIBIT'];
    $perm = $perm_map[$c->permission] ?? $c->permission;
    echo "  {$c->role_shortname} | {$c->capability} | {$perm}\n";
}
if (empty($scorm_overrides)) {
    echo "  (no custom SCORM capability overrides found - using defaults)\n";
}

// 4. All defined interactive video capabilities
echo "\n--- [4] ALL INTERACTIVE VIDEO CAPABILITIES (defined) ---\n";
$all_iv_caps = $DB->get_records_sql(
    "SELECT name, component FROM {capabilities} WHERE name LIKE '%interactivevideo%' ORDER BY name"
);
foreach ($all_iv_caps as $c) {
    echo "  {$c->name} ({$c->component})\n";
}
if (empty($all_iv_caps)) {
    echo "  (no interactive video capabilities found)\n";
}

// 5. Interactive video role capability overrides
echo "\n--- [5] INTERACTIVE VIDEO CAPABILITY OVERRIDES ---\n";
$iv_overrides = $DB->get_records_sql(
    "SELECT rc.id, rc.capability, rc.permission, r.shortname as role_shortname
     FROM {role_capabilities} rc
     JOIN {role} r ON r.id = rc.roleid
     WHERE rc.capability LIKE '%interactivevideo%'
     ORDER BY r.shortname, rc.capability"
);
foreach ($iv_overrides as $c) {
    $perm_map = [1 => 'ALLOW', -1 => 'PREVENT', -1000 => 'PROHIBIT'];
    $perm = $perm_map[$c->permission] ?? $c->permission;
    echo "  {$c->role_shortname} | {$c->capability} | {$perm}\n";
}
if (empty($iv_overrides)) {
    echo "  (no custom IV capability overrides found)\n";
}

// 6. Check default role capabilities for SCORM (what students/teachers get by default)
echo "\n--- [6] DEFAULT ROLE DEFINITIONS FOR SCORM ---\n";
$key_roles = ['student', 'teacher', 'editingteacher', 'manager', 'coursecreator'];
$scorm_cap_names = array_column($all_scorm_caps, 'name');
foreach ($key_roles as $role_shortname) {
    $role = $DB->get_record('role', ['shortname' => $role_shortname]);
    if (!$role) continue;
    echo "  [{$role_shortname}]\n";
    foreach ($scorm_cap_names as $cap_name) {
        // Check in role_capabilities
        $rc = $DB->get_record('role_capabilities', [
            'roleid' => $role->id,
            'capability' => $cap_name,
            'contextid' => 1, // system context
        ]);
        if ($rc) {
            $perm_map = [1 => 'ALLOW', -1 => 'PREVENT', -1000 => 'PROHIBIT'];
            $perm = $perm_map[$rc->permission] ?? $rc->permission;
            echo "    {$cap_name} = {$perm}\n";
        }
    }
}

// 7. Check file/resource related capabilities
echo "\n--- [7] FILE/RESOURCE DOWNLOAD CAPABILITIES ---\n";
$file_caps = $DB->get_records_sql(
    "SELECT name FROM {capabilities} 
     WHERE name LIKE '%resource%' 
        OR name LIKE '%mod/folder%'
        OR name LIKE '%backup%export%'
     ORDER BY name"
);
foreach ($file_caps as $c) {
    echo "  {$c->name}\n";
}

// 8. Check forcedownload and other security settings
echo "\n--- [8] SECURITY SETTINGS ---\n";
$settings = [
    'forcelogin' => get_config('core', 'forcelogin'),
    'forceloginforprofiles' => get_config('core', 'forceloginforprofiles'),
    'enablewebservices' => get_config('core', 'enablewebservices'),
    'allowguestaccess' => get_config('core', 'allowguestaccess'),
    'guestloginbutton' => get_config('core', 'guestloginbutton'),
];
foreach ($settings as $k => $v) {
    echo "  {$k} = " . ($v !== false ? $v : '(not set)') . "\n";
}

// 9. SCORM module settings
echo "\n--- [9] SCORM MODULE SETTINGS ---\n";
$scorm_configs = $DB->get_records_sql(
    "SELECT name, value FROM {config_plugins} WHERE plugin = 'scorm' ORDER BY name"
);
foreach ($scorm_configs as $c) {
    echo "  {$c->name} = {$c->value}\n";
}
if (empty($scorm_configs)) {
    echo "  (using all Moodle defaults)\n";
}

// 10. Resource module settings
echo "\n--- [10] RESOURCE MODULE SETTINGS ---\n";
$res_configs = $DB->get_records_sql(
    "SELECT name, value FROM {config_plugins} WHERE plugin = 'resource' ORDER BY name"
);
foreach ($res_configs as $c) {
    echo "  {$c->name} = {$c->value}\n";
}

// 11. Admin users
echo "\n--- [11] ADMIN USERS ---\n";
$admins = get_config('core', 'siteadmins');
echo "  Site admin user IDs: {$admins}\n";
$admin_ids = explode(',', $admins);
foreach ($admin_ids as $aid) {
    $u = $DB->get_record('user', ['id' => trim($aid)]);
    if ($u) echo "  Admin: {$u->username} ({$u->email})\n";
}

// 12. Check all users and their roles
echo "\n--- [12] USER-ROLE ASSIGNMENTS ---\n";
$users = $DB->get_records_sql(
    "SELECT u.id, u.username, u.email, u.firstname, u.lastname
     FROM {user} u
     WHERE u.deleted = 0 AND u.username NOT IN ('guest')
     ORDER BY u.id"
);
foreach ($users as $u) {
    $role_assignments = $DB->get_records_sql(
        "SELECT ra.id, r.shortname, ctx.contextlevel
         FROM {role_assignments} ra
         JOIN {role} r ON r.id = ra.roleid
         JOIN {context} ctx ON ctx.id = ra.contextid
         WHERE ra.userid = ?",
        [$u->id]
    );
    $roles_str = '';
    foreach ($role_assignments as $ra) {
        $roles_str .= "{$ra->shortname}(ctx:{$ra->contextlevel}) ";
    }
    echo "  {$u->username} ({$u->firstname} {$u->lastname}) - Roles: " . ($roles_str ?: '(none)') . "\n";
}

// 13. Check mod/resource backup capabilities
echo "\n--- [13] BACKUP/EXPORT CAPABILITIES FOR KEY ROLES ---\n";
$backup_caps = $DB->get_records_sql(
    "SELECT name FROM {capabilities} WHERE name LIKE '%backup%' ORDER BY name"
);
foreach ($key_roles as $role_shortname) {
    $role = $DB->get_record('role', ['shortname' => $role_shortname]);
    if (!$role) continue;
    echo "  [{$role_shortname}]\n";
    foreach ($backup_caps as $cap) {
        $rc = $DB->get_record('role_capabilities', [
            'roleid' => $role->id,
            'capability' => $cap->name,
            'contextid' => 1,
        ]);
        if ($rc && $rc->permission == 1) {
            echo "    {$cap->name} = ALLOW\n";
        }
    }
}

echo "\n==============================================\n";
echo "  AUDIT COMPLETE\n";
echo "==============================================\n";
