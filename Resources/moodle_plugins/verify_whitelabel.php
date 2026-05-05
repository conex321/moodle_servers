<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

echo "=== WHITE-LABEL VERIFICATION ===\n\n";

// 1. Check Edwiser blocks
$blocks = $DB->get_records_sql(
    "SELECT id, blockname FROM {block_instances} WHERE blockname LIKE '%edwiser%'"
);
echo "1. Edwiser blocks remaining: " . count($blocks) . "\n";
foreach ($blocks as $b) {
    echo "   - #{$b->id}: {$b->blockname}\n";
}

// 2. Check CSS
$css = get_config('theme_remui', 'customcss');
echo "2. Custom CSS applied: " . (strlen($css) > 100 ? 'YES (' . strlen($css) . ' chars)' : 'NO') . "\n";

// 3. Check notification processors
$procs = $DB->get_records('message_processors');
echo "3. Message processors:\n";
foreach ($procs as $p) {
    echo "   - {$p->name}: " . ($p->enabled ? 'ENABLED' : 'disabled') . "\n";
}

// 4. Check Edwiser settings
echo "4. Edwiser settings:\n";
echo "   - poweredby: " . var_export(get_config('theme_remui', 'poweredby'), true) . "\n";
echo "   - enablefeedback: " . var_export(get_config('theme_remui', 'enablefeedback'), true) . "\n";
echo "   - enableusagetracking: " . var_export(get_config('theme_remui', 'enableusagetracking'), true) . "\n";
echo "   - enableproductnotification: " . var_export(get_config('theme_remui', 'enableproductnotification'), true) . "\n";
echo "   - enablehelpsupport: " . var_export(get_config('theme_remui', 'enablehelpsupport'), true) . "\n";
echo "   - docroot: " . var_export(get_config('core', 'docroot'), true) . "\n";

// 5. Check for remaining 'Edwiser' text in courses
$courses = $DB->get_records_sql(
    "SELECT id, fullname FROM {course} WHERE fullname LIKE '%Edwiser%' OR shortname LIKE '%Edwiser%'"
);
echo "5. Courses with 'Edwiser' in name: " . count($courses) . "\n";

// 6. Check capabilities
echo "6. Edwiser capability restrictions:\n";
$edw_caps = $DB->get_records_sql(
    "SELECT DISTINCT rc.capability, r.shortname as role, rc.permission
     FROM {role_capabilities} rc
     JOIN {role} r ON r.id = rc.roleid
     WHERE rc.capability LIKE '%edwiser%'
     ORDER BY rc.capability, r.shortname"
);
foreach ($edw_caps as $rc) {
    $perm = $rc->permission == -1 ? 'PREVENT' : ($rc->permission == 1 ? 'ALLOW' : $rc->permission);
    echo "   - {$rc->capability} | {$rc->role}: {$perm}\n";
}

// 7. Check navigation nodes containing edwiser
echo "7. Custom menu items with 'Edwiser': ";
$menu = get_config('core', 'custommenuitems');
echo (stripos($menu ?: '', 'edwiser') !== false ? 'FOUND' : 'clean') . "\n";

echo "\n=== VERIFICATION COMPLETE ===\n";
