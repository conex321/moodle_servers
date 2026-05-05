<?php
/**
 * Deep diagnostic: check RemUI frontpage settings and what values they need
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->dirroot . '/lib/adminlib.php');

global $DB;

echo "═══ DEEP RemUI Front Page Diagnostic ═══\n\n";

// Key settings with their actual values
$settings = [
    'frontpagechooser', 'frontpageimagecontent', 'contenttype',
    'slidercount', 'sliderautoplay', 'slideinterval',
    'enablefrontpageaboutus', 'frontpageaboutusheading', 'frontpageaboutustext',
    'frontpageblockdisplay', 'frontpageblockheading', 'frontpageblockdesc',
    'testimonialcount',
    'frontpageblocksection1', 'frontpageblockdescriptionsection1',
    'frontpageblockiconsection1',
    'enablesectionbutton',
    'slideimage1', 'slidertext1', 'sliderurl1', 'sliderbuttontext1',
];

foreach ($settings as $s) {
    $val = get_config('theme_remui', $s);
    $display = $val;
    if (strlen($val) > 80) {
        $display = substr($val, 0, 80) . '...';
    }
    echo "  $s = " . var_export($display, true) . "\n";
}

echo "\n─── About Us Logic Check ───\n";
$displayaboutus = get_config('theme_remui', 'frontpageblockdisplay');
echo "frontpageblockdisplay = $displayaboutus\n";
echo "  Value 1 = DISABLED (returns false)\n";
echo "  Value 2 = IN ROW layout\n";
echo "  Value 3 = IN COLUMN layout\n";
echo "  CURRENT: " . ($displayaboutus == 1 ? "*** DISABLED ***" : "ENABLED") . "\n";

echo "\n─── Testimonial Logic Check ───\n";
$enableabout = get_config('theme_remui', 'enablefrontpageaboutus');
echo "enablefrontpageaboutus = $enableabout\n";
echo "  CURRENT: " . ($enableabout ? "ENABLED" : "*** DISABLED ***") . "\n";

echo "\n─── Slider Logic Check ───\n";
$fpic = get_config('theme_remui', 'frontpageimagecontent');
echo "frontpageimagecontent = $fpic\n";
echo "  Value 1/true = Dynamic slider mode\n";
echo "  Value 0/false = Static image mode\n";
echo "  CURRENT: " . ($fpic ? "SLIDER (correct)" : "STATIC") . "\n";

echo "\n─── Mustache Templates ───\n";
$templates = glob($CFG->dirroot . '/theme/remui/templates/*frontpage*');
foreach ($templates as $t) {
    echo "  " . basename($t) . "\n";
}
$templates2 = glob($CFG->dirroot . '/theme/remui/templates/*slider*');
foreach ($templates2 as $t) {
    echo "  " . basename($t) . "\n";
}
$templates3 = glob($CFG->dirroot . '/theme/remui/templates/*home*');
foreach ($templates3 as $t) {
    echo "  " . basename($t) . "\n";
}

echo "\n─── Renderer Check ───\n";
$renderers = glob($CFG->dirroot . '/theme/remui/classes/output/*.php');
foreach ($renderers as $r) {
    echo "  " . basename($r);
    $content = file_get_contents($r);
    if (strpos($content, 'frontpagechooser') !== false) {
        echo " [HAS frontpagechooser]";
    }
    if (strpos($content, 'get_slider_data') !== false) {
        echo " [HAS slider]";
    }
    if (strpos($content, 'get_testimonial_data') !== false) {
        echo " [HAS testimonial]";
    }
    echo "\n";
}

echo "\n═══ End Deep Diagnostic ═══\n";
