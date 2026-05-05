<?php
/**
 * Clean up remaining Edwiser references in course names and descriptions
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

echo "--- Searching for courses with 'Edwiser' in name ---\n";
$courses = $DB->get_records_sql(
    "SELECT id, fullname, shortname, summary FROM {course} WHERE fullname LIKE '%Edwiser%' OR shortname LIKE '%Edwiser%' OR summary LIKE '%Edwiser%'"
);

foreach ($courses as $c) {
    echo "  Course ID {$c->id}: {$c->fullname} ({$c->shortname})\n";
    echo "    Summary: " . substr(strip_tags($c->summary), 0, 100) . "\n";
    
    // Update the name removing Edwiser references
    $new_name = str_ireplace('Edwiser ', '', $c->fullname);
    $new_name = str_ireplace('Edwiser', '', $new_name);
    $new_shortname = str_ireplace('Edwiser ', '', $c->shortname);
    $new_shortname = str_ireplace('Edwiser', '', $new_shortname);
    $new_summary = str_ireplace('Edwiser ', '', $c->summary);
    $new_summary = str_ireplace('Edwiser', '', $new_summary);
    
    $DB->update_record('course', (object)[
        'id' => $c->id,
        'fullname' => trim($new_name),
        'shortname' => trim($new_shortname),
        'summary' => trim($new_summary),
    ]);
    echo "    Updated to: {$new_name}\n";
}

if (empty($courses)) {
    echo "  No courses found with 'Edwiser' in their names.\n";
}

echo "\n--- Checking for Edwiser in any visible text strings ---\n";

// Check custommenuitems
$custommenu = get_config('core', 'custommenuitems');
if ($custommenu && stripos($custommenu, 'edwiser') !== false) {
    echo "  Custom menu contains 'Edwiser' — cleaning...\n";
    $custommenu = str_ireplace('Edwiser', '', $custommenu);
    set_config('custommenuitems', $custommenu);
}

// Check footer text
$footer_text = get_config('theme_remui', 'footerbottomtext');
if ($footer_text && stripos($footer_text, 'edwiser') !== false) {
    echo "  Footer text contains 'Edwiser' — cleaning...\n";
    $footer_text = str_ireplace('Edwiser', '', $footer_text);
    set_config('footerbottomtext', $footer_text, 'theme_remui');
}

// Check site summary/description
$site = $DB->get_record('course', ['id' => 1]); // site course
if (stripos($site->summary, 'edwiser') !== false || stripos($site->fullname, 'edwiser') !== false) {
    echo "  Site course contains 'Edwiser' — cleaning...\n";
    $site->fullname = str_ireplace('Edwiser', '', $site->fullname);
    $site->summary = str_ireplace('Edwiser', '', $site->summary);
    $DB->update_record('course', $site);
}

// Check blocks that mention Edwiser (block instances with edwiser text)
echo "\n--- Looking for block instances with Edwiser text ---\n";
$blocks = $DB->get_records_sql(
    "SELECT id, blockname, configdata FROM {block_instances} WHERE configdata LIKE '%Edwiser%'"
);
foreach ($blocks as $b) {
    echo "  Block {$b->blockname} (id: {$b->id}) has Edwiser in config\n";
    $config = str_ireplace('Edwiser', '', $b->configdata);
    $DB->update_record('block_instances', (object)[
        'id' => $b->id,
        'configdata' => $config,
    ]);
    echo "    Cleaned.\n";
}

if (empty($blocks)) {
    echo "  No block instances with 'Edwiser' text found.\n";
}

// Purge caches again
purge_all_caches();
echo "\n  Caches purged.\n";
echo "DONE\n";
