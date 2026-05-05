<?php
/**
 * Edwiser License Activation & Branding Removal Script
 * Run inside Docker container: php /tmp/plugins/activate_licenses.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');

echo "==============================================\n";
echo "  Edwiser License Activation & Branding Setup\n";
echo "==============================================\n\n";

// ---------- PART 1: Gather System Info ----------
echo "--- System Info ---\n";
echo "Moodle version: " . $CFG->release . "\n";
echo "Theme: " . $CFG->theme . "\n";
echo "WWW root: " . $CFG->wwwroot . "\n\n";

// ---------- PART 2: List all Edwiser plugins ----------
echo "--- Installed Edwiser Plugins ---\n";
$plugin_types = core_plugin_manager::instance()->get_plugins();
$edwiser_plugins = [];
foreach ($plugin_types as $type => $plugins) {
    foreach ($plugins as $name => $info) {
        if (stripos($name, 'edwiser') !== false || stripos($name, 'remui') !== false) {
            $edwiser_plugins[] = ['type' => $type, 'name' => $name, 'version' => $info->versiondb ?? 'N/A'];
            echo "  [$type] $name (v" . ($info->versiondb ?? 'N/A') . ")\n";
        }
    }
}
echo "  Total: " . count($edwiser_plugins) . " Edwiser plugins found\n\n";

// ---------- PART 3: Check existing license data in DB ----------
echo "--- Current License Status (from config_plugins table) ---\n";
$license_configs = $DB->get_records_sql(
    "SELECT * FROM {config_plugins} WHERE name LIKE '%license%' AND (plugin LIKE '%edwiser%' OR plugin LIKE '%remui%')"
);
if (empty($license_configs)) {
    echo "  No existing license configurations found.\n";
} else {
    foreach ($license_configs as $cfg) {
        echo "  Plugin: {$cfg->plugin} | Name: {$cfg->name} | Value: {$cfg->value}\n";
    }
}
echo "\n";

// Also check for any edd_license_key entries
$edd_keys = $DB->get_records_sql(
    "SELECT * FROM {config_plugins} WHERE name LIKE '%edd%' OR name LIKE '%license_key%' OR name LIKE '%licensekey%'"
);
if (!empty($edd_keys)) {
    echo "--- EDD / License Key Entries ---\n";
    foreach ($edd_keys as $ek) {
        echo "  Plugin: {$ek->plugin} | Name: {$ek->name} | Value: {$ek->value}\n";
    }
    echo "\n";
}

// ---------- PART 4: Check RemUI specific settings ----------
echo "--- RemUI Theme Settings ---\n";
$remui_configs = $DB->get_records('config_plugins', ['plugin' => 'theme_remui']);
$remui_settings = [];
foreach ($remui_configs as $rc) {
    $remui_settings[$rc->name] = $rc->value;
    // Show only relevant settings
    if (stripos($rc->name, 'license') !== false ||
        stripos($rc->name, 'footer') !== false ||
        stripos($rc->name, 'powered') !== false ||
        stripos($rc->name, 'brand') !== false ||
        stripos($rc->name, 'help') !== false ||
        stripos($rc->name, 'custom') !== false) {
        $val = strlen($rc->value) > 100 ? substr($rc->value, 0, 100) . '...' : $rc->value;
        echo "  {$rc->name} = {$val}\n";
    }
}
echo "  Total RemUI settings: " . count($remui_configs) . "\n\n";

// ---------- PART 5: List ALL setting names for RemUI ----------
echo "--- All RemUI Setting Names ---\n";
foreach ($remui_settings as $name => $val) {
    echo "  $name\n";
}
echo "\n";

// ---------- PART 6: Check for Edwiser Bridge settings ----------
echo "--- Edwiser Bridge Settings ---\n";
$bridge_configs = $DB->get_records_sql(
    "SELECT * FROM {config_plugins} WHERE plugin LIKE '%bridge%' OR plugin LIKE '%eb_%'"
);
if (empty($bridge_configs)) {
    echo "  No bridge settings found (plugin may not be installed on Moodle side).\n";
} else {
    foreach ($bridge_configs as $bc) {
        echo "  Plugin: {$bc->plugin} | {$bc->name} = {$bc->value}\n";
    }
}
echo "\n";

// ---------- PART 7: Check for Edwiser Forms settings ----------
echo "--- Edwiser Forms Settings ---\n";
$forms_configs = $DB->get_records_sql(
    "SELECT * FROM {config_plugins} WHERE plugin LIKE '%edwiserform%'"
);
if (empty($forms_configs)) {
    echo "  No forms settings found.\n";
} else {
    foreach ($forms_configs as $fc) {
        $val = strlen($fc->value) > 100 ? substr($fc->value, 0, 100) . '...' : $fc->value;
        echo "  {$fc->name} = {$val}\n";
    }
}
echo "\n";

echo "==============================================\n";
echo "  Diagnostic Complete\n";
echo "==============================================\n";
