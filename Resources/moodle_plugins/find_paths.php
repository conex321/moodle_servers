<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $CFG;
echo "dirroot: " . $CFG->dirroot . "\n";
echo "theme dir: " . $CFG->dirroot . "/theme\n";
$dirs = glob($CFG->dirroot . "/theme/*", GLOB_ONLYDIR);
foreach ($dirs as $d) {
    echo "  " . basename($d) . "\n";
}
