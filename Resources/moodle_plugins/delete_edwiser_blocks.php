<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');

echo "--- Removing Edwiser Block Instances ---\n";

$blocks = $DB->get_records_sql(
    "SELECT id, blockname, pagetypepattern FROM {block_instances} WHERE blockname LIKE '%edwiser%'"
);

echo "Found " . count($blocks) . " Edwiser blocks to remove.\n";

foreach ($blocks as $b) {
    // Delete block positions first (foreign key)
    $DB->delete_records('block_positions', ['blockinstanceid' => $b->id]);
    // Delete the block instance
    $DB->delete_records('block_instances', ['id' => $b->id]);
    echo "  Deleted: {$b->blockname} #{$b->id} (page: {$b->pagetypepattern})\n";
}

// Verify
$remaining = $DB->count_records_sql(
    "SELECT COUNT(*) FROM {block_instances} WHERE blockname LIKE '%edwiser%'"
);
echo "\nRemaining Edwiser blocks: {$remaining}\n";

// Purge caches
purge_all_caches();
theme_reset_all_caches();
echo "Caches purged.\n";
echo "DONE.\n";
