<?php
/**
 * Dump all RemUI frontpage-related settings
 * Run: docker exec moodle-app php /tmp/dump_remui_frontpage.php
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

echo "=== ALL REMUI SETTINGS (frontpage-related) ===\n\n";

// Get ALL remui settings
$configs = $DB->get_records('config_plugins', ['plugin' => 'theme_remui']);
$keywords = ['frontpage', 'slider', 'section', 'block', 'homepage', 'hero', 'banner',
             'about', 'feature', 'counter', 'testimonial', 'course', 'team', 'faq',
             'footer', 'logo', 'favicon', 'sitename', 'header', 'category', 'card',
             'layout', 'page', 'builder', 'static', 'content'];

foreach ($configs as $c) {
    $name_lower = strtolower($c->name);
    $match = false;
    foreach ($keywords as $kw) {
        if (strpos($name_lower, $kw) !== false) {
            $match = true;
            break;
        }
    }
    if ($match) {
        $val = strlen($c->value) > 300 ? substr($c->value, 0, 300) . '...[TRUNCATED]' : $c->value;
        echo "[{$c->name}] => {$val}\n\n";
    }
}

echo "\n=== ALL REMUI SETTING NAMES (complete list) ===\n\n";
foreach ($configs as $c) {
    $len = strlen($c->value);
    echo "  {$c->name} ({$len} chars)\n";
}

echo "\n=== CURRENT THEME ===\n";
echo "Active theme: " . get_config('core', 'theme') . "\n";

echo "\n=== FRONTPAGE SETTINGS ===\n";
echo "frontpage: " . get_config('core', 'frontpage') . "\n";
echo "frontpageloggedin: " . get_config('core', 'frontpageloggedin') . "\n";
echo "defaulthomepage: " . get_config('core', 'defaulthomepage') . "\n";

echo "\n=== SITE INFO ===\n";
$site = $DB->get_record('course', ['id' => 1]);
echo "Site fullname: " . $site->fullname . "\n";
echo "Site shortname: " . $site->shortname . "\n";
echo "Site summary: " . substr($site->summary ?? '', 0, 500) . "\n";

echo "\nDone.\n";
