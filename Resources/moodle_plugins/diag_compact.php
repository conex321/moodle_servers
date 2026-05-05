<?php
/**
 * Targeted Edwiser diagnostic - compact output
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');

echo "THEME: " . $CFG->theme . "\n";

// Count Edwiser plugins
$pm = core_plugin_manager::instance();
$all = $pm->get_plugins();
$count = 0;
foreach ($all as $type => $plugins) {
    foreach ($plugins as $name => $info) {
        if (stripos($name, 'edwiser') !== false || stripos($name, 'remui') !== false) {
            echo "PLUGIN: [$type] $name\n";
            $count++;
        }
    }
}
echo "TOTAL_PLUGINS: $count\n";

// License-related config
$recs = $DB->get_records_sql(
    "SELECT id, plugin, name, value FROM {config_plugins} 
     WHERE (name LIKE '%license%' OR name LIKE '%edd%' OR name LIKE '%licensekey%')
     AND (plugin LIKE '%edwiser%' OR plugin LIKE '%remui%' OR plugin LIKE '%remuiformat%')
     ORDER BY plugin, name"
);
echo "LICENSE_ENTRIES: " . count($recs) . "\n";
foreach ($recs as $r) {
    echo "LIC: {$r->plugin} | {$r->name} = {$r->value}\n";
}

// RemUI footer/powered-by settings
$footer_keys = ['poweredby', 'footerbottomtext', 'footerbottomlink', 'customcss', 'customfootertext'];
foreach ($footer_keys as $k) {
    $val = $DB->get_field('config_plugins', 'value', ['plugin' => 'theme_remui', 'name' => $k]);
    if ($val !== false) {
        $disp = strlen($val) > 200 ? substr($val, 0, 200) . '...' : $val;
        echo "REMUI_SETTING: $k = $disp\n";
    }
}

// Check for edwiserform license  
$form_lic = $DB->get_records_sql(
    "SELECT id, plugin, name, value FROM {config_plugins} WHERE plugin LIKE '%edwiserform%' AND name LIKE '%license%'"
);
foreach ($form_lic as $fl) {
    echo "FORM_LIC: {$fl->plugin} | {$fl->name} = {$fl->value}\n";
}

// Check for edwiserreports license
$rep_lic = $DB->get_records_sql(
    "SELECT id, plugin, name, value FROM {config_plugins} WHERE plugin LIKE '%edwiserreports%' AND name LIKE '%license%'"
);
foreach ($rep_lic as $rl) {
    echo "REPORTS_LIC: {$rl->plugin} | {$rl->name} = {$rl->value}\n";
}

echo "DONE\n";
