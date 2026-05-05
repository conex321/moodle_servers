<?php
/**
 * Diagnostic script to check RemUI front page configuration
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

global $DB;

echo "═══ RemUI Front Page Diagnostics ═══\n\n";

// Check theme
echo "Theme: " . get_config('core', 'theme') . "\n";

// Check front page chooser
echo "frontpagechooser: " . get_config('theme_remui', 'frontpagechooser') . "\n";
echo "frontpageimagecontent: " . get_config('theme_remui', 'frontpageimagecontent') . "\n";
echo "contenttype: " . get_config('theme_remui', 'contenttype') . "\n";

// Check slider
echo "\n─── Slider ───\n";
echo "slidercount: " . get_config('theme_remui', 'slidercount') . "\n";
echo "slideimage1: " . get_config('theme_remui', 'slideimage1') . "\n";
echo "slideimage2: " . get_config('theme_remui', 'slideimage2') . "\n";
echo "slideimage3: " . get_config('theme_remui', 'slideimage3') . "\n";
echo "slidertext1 (len): " . strlen(get_config('theme_remui', 'slidertext1')) . "\n";

// Check slide image files actually exist
$fs = get_file_storage();
$sysctx = context_system::instance();
for ($i = 1; $i <= 3; $i++) {
    $files = $fs->get_area_files($sysctx->id, 'theme_remui', "slideimage$i", 0, 'filename', false);
    echo "slideimage$i files: " . count($files);
    foreach ($files as $f) {
        echo " -> " . $f->get_filename() . " (" . $f->get_filesize() . " bytes)";
    }
    echo "\n";
}

// Check feature blocks
echo "\n─── Feature Blocks ───\n";
echo "frontpageblockdisplay: " . get_config('theme_remui', 'frontpageblockdisplay') . "\n";
echo "frontpageblockheading: " . get_config('theme_remui', 'frontpageblockheading') . "\n";

// Check about us
echo "\n─── About Us ───\n";
echo "enablefrontpageaboutus: " . get_config('theme_remui', 'enablefrontpageaboutus') . "\n";
echo "frontpageaboutusheading: " . get_config('theme_remui', 'frontpageaboutusheading') . "\n";

// Check testimonials
echo "\n─── Testimonials ───\n";
echo "testimonialcount: " . get_config('theme_remui', 'testimonialcount') . "\n";
echo "testimonialname1: " . get_config('theme_remui', 'testimonialname1') . "\n";

// Check additionalhtmlhead
echo "\n─── Additional HTML ───\n";
$head = get_config('core', 'additionalhtmlhead');
echo "additionalhtmlhead length: " . strlen($head) . "\n";
echo "Contains <style>: " . (strpos($head, '<style') !== false ? 'YES' : 'NO') . "\n";
echo "Contains FORCE dark: " . (strpos($head, 'FORCE dark navy') !== false ? 'YES' : 'NO') . "\n";

// Check Moodle frontpage setting
echo "\n─── Core Frontpage ───\n";
echo "frontpage: " . get_config('core', 'frontpage') . "\n";
echo "frontpageloggedin: " . get_config('core', 'frontpageloggedin') . "\n";
echo "forcelogin: " . get_config('core', 'forcelogin') . "\n";

// Check if hero images exist on disk
echo "\n─── Hero Images on Disk ───\n";
for ($i = 1; $i <= 3; $i++) {
    $path = "/tmp/frontpage_images/hero$i.png";
    echo "hero$i.png: " . (file_exists($path) ? filesize($path) . " bytes" : "NOT FOUND") . "\n";
}

echo "\n═══ End Diagnostics ═══\n";
