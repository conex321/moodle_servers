<?php
/**
 * Configure the filter_translations plugin and enable the content translation filter.
 *
 * Usage:
 *   docker cp configure_translations.php moodle-app:/var/www/html/configure_translations.php
 *   docker exec -u www-data moodle-app bash -c "php /var/www/html/configure_translations.php [--api-key=YOUR_KEY]"
 *
 * What this script does:
 *   1. Enables the filter_translations filter globally (content + headings)
 *   2. Enables Google Translate as the translation provider
 *   3. Sets the Google Translate API key (if provided via --api-key)
 *   4. Configures logging for missing translations
 *   5. Sets application-level caching for performance
 *   6. Ensures zh_cn and vi language packs are available
 */

define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filterlib.php');

// Parse CLI options.
list($options, $unrecognized) = cli_get_params([
    'api-key' => null,
    'help'    => false,
], [
    'k' => 'api-key',
    'h' => 'help',
]);

if ($options['help']) {
    echo "Configure filter_translations plugin for multilingual support.\n\n";
    echo "Options:\n";
    echo "  --api-key=KEY   Google Cloud Translation API key\n";
    echo "  -h, --help      Show this help\n";
    exit(0);
}

echo "=== Configuring Content Translations Filter ===\n\n";

// --- 1. Enable the filter globally ---
echo "1. Enabling filter_translations filter...\n";

// Check if filter exists.
$filters = filter_get_all_installed();
if (!isset($filters['translations'])) {
    echo "   ERROR: filter_translations plugin is not installed!\n";
    echo "   Install it first in /var/www/html/public/filter/translations/\n";
    exit(1);
}

// Enable filter globally: FILTER_ACTIVE = 1, apply to content and headings.
filter_set_global_state('translations', TEXTFILTER_ON);
echo "   Filter enabled globally.\n";

// Set to apply to content and headings (-1 = content + headings, 0 = content only).
// In Moodle, filter_set_applies_to_strings controls this.
filter_set_applies_to_strings('translations', true);
echo "   Filter set to apply to content and headings.\n";

// --- 2. Configure Google Translate ---
echo "\n2. Configuring Google Translate provider...\n";

set_config('google_enable', 1, 'filter_translations');
echo "   Google Translate enabled.\n";

set_config('google_apiendpoint', 'https://translation.googleapis.com/language/translate/v2', 'filter_translations');
echo "   API endpoint set.\n";

set_config('google_backoffonerror', 1, 'filter_translations');
echo "   Back-off on error enabled.\n";

if (!empty($options['api-key'])) {
    set_config('google_apikey', $options['api-key'], 'filter_translations');
    echo "   API key configured.\n";
} else {
    $existing = get_config('filter_translations', 'google_apikey');
    if (empty($existing)) {
        echo "   WARNING: No API key set. Use --api-key=YOUR_KEY or set it in:\n";
        echo "   Site Administration > Plugins > Filters > Content translations\n";
    } else {
        echo "   API key already configured (not overwriting).\n";
    }
}

// --- 3. Configure caching and performance ---
echo "\n3. Configuring performance settings...\n";

// Use application-level caching for best performance (cache_store::MODE_APPLICATION = 2).
set_config('cachingmode', 2, 'filter_translations');
echo "   Caching mode set to application-level.\n";

set_config('showperfdata', 0, 'filter_translations');
echo "   Performance data display disabled.\n";

// --- 4. Configure logging ---
echo "\n4. Configuring logging...\n";

set_config('logmissing', 1, 'filter_translations');
echo "   Missing translation logging enabled.\n";

set_config('logstale', 0, 'filter_translations');
set_config('loghistory', 0, 'filter_translations');
echo "   Stale/history logging disabled (can enable later).\n";

// --- 5. Verify language packs ---
echo "\n5. Verifying language packs...\n";

$installed = get_string_manager()->get_list_of_translations(true);
$required = ['zh_cn' => 'Simplified Chinese', 'vi' => 'Vietnamese'];

foreach ($required as $code => $name) {
    if (isset($installed[$code])) {
        echo "   $name ($code): INSTALLED\n";
    } else {
        echo "   $name ($code): NOT INSTALLED - install via Site Admin > Language > Language packs\n";
    }
}

// --- 6. Purge caches ---
echo "\n6. Purging caches...\n";
purge_all_caches();
echo "   Caches purged.\n";

// --- Summary ---
echo "\n=== Configuration Complete ===\n";
echo "\nNext steps:\n";
echo "  1. Set Google Translate API key (if not done above)\n";
echo "  2. Visit: Site Administration > Plugins > Filters > Content translations\n";
echo "  3. Click 'Manage translations' to trigger bulk translation\n";
echo "  4. Users can switch language via the nav bar dropdown\n";
echo "\nFilter status: ACTIVE\n";
echo "Translation provider: Google Translate\n";
echo "Target languages: zh_cn (Chinese), vi (Vietnamese)\n";
