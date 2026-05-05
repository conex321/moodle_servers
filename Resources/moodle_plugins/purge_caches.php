<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');

// Purge all caches
purge_all_caches();
echo "All caches purged\n";

// Reset theme caches
theme_reset_all_caches();
echo "Theme caches reset\n";

echo "DONE\n";
