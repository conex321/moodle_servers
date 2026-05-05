<?php
/**
 * Install language packs for Simplified Chinese and Vietnamese.
 *
 * Usage: docker cp install_langpacks.php moodle-app:/var/www/html/install_langpacks.php
 *        docker exec -u www-data moodle-app bash -c "php /var/www/html/install_langpacks.php"
 */

define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/componentlib.class.php');

// Languages to install.
$langs = ['zh_cn', 'vi'];

echo "Creating lang directory at: {$CFG->dataroot}/lang\n";
@mkdir($CFG->dataroot . '/lang', 0777, true);

echo "Checking write permissions on {$CFG->dataroot}/lang ...\n";
if (!is_writable($CFG->dataroot . '/lang')) {
    echo "ERROR: {$CFG->dataroot}/lang is not writable!\n";
    exit(1);
}

echo "Initializing lang_installer...\n";
$installer = new lang_installer();

echo "Fetching remote language list...\n";
$remotelist = $installer->get_remote_list_of_languages();
if (empty($remotelist)) {
    echo "ERROR: Could not fetch remote language list from download.moodle.org\n";
    echo "Check internet connectivity from this container.\n";
    exit(1);
}
echo "Found " . count($remotelist) . " remote language packs available.\n";

// Check which of our target langs exist remotely.
$remotelangs = [];
foreach ($remotelist as $entry) {
    $remotelangs[$entry[0]] = $entry[1]; // langcode => md5
}

foreach ($langs as $lang) {
    if (!isset($remotelangs[$lang])) {
        echo "WARNING: '$lang' not found in remote language list!\n";
        continue;
    }

    // Check if already installed.
    $installed = get_string_manager()->get_list_of_translations(true);
    if (isset($installed[$lang])) {
        echo "Language pack '$lang' is already installed.\n";
        continue;
    }

    echo "Installing language pack '$lang'...\n";

    try {
        $installer->set_queue($lang);
        $results = $installer->run();

        foreach ($results as $langcode => $status) {
            switch ($status) {
                case lang_installer::RESULT_INSTALLED:
                    echo "SUCCESS: '$langcode' installed.\n";
                    break;
                case lang_installer::RESULT_UPTODATE:
                    echo "INFO: '$langcode' already up to date.\n";
                    break;
                case lang_installer::RESULT_DOWNLOADERROR:
                    echo "ERROR: Download failed for '$langcode'.\n";
                    break;
                default:
                    echo "UNKNOWN STATUS ($status) for '$langcode'.\n";
            }
        }
    } catch (Exception $e) {
        echo "EXCEPTION installing '$lang': " . $e->getMessage() . "\n";
    }
}

// Reset string cache.
get_string_manager()->reset_caches();

// Show all installed languages.
$installed = get_string_manager()->get_list_of_translations(true);
echo "\nInstalled language packs:\n";
foreach ($installed as $code => $name) {
    echo "  $code: $name\n";
}

// Check filesystem.
echo "\nLanguage directories in {$CFG->dataroot}/lang:\n";
$dirs = glob($CFG->dataroot . '/lang/*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    echo "  " . basename($dir) . "\n";
}

echo "\nDone.\n";
