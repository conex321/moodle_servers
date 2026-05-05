<?php
/**
 * Fix Bootstrap 4→5 data-toggle mismatch for RemUI category dropdowns.
 *
 * Injects a small JS snippet into Moodle's additionalhtmlfooter that converts
 * all data-toggle="dropdown" attributes to data-bs-toggle="dropdown" so that
 * Bootstrap 5 (used by Moodle 5.x) can initialise the dropdowns correctly.
 *
 * Usage (inside Moodle container):
 *   php /tmp/fix_category_dropdowns.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');

global $CFG;

echo "═══════════════════════════════════════════════════\n";
echo "  FIX: Bootstrap 4→5 dropdown attribute mismatch\n";
echo "═══════════════════════════════════════════════════\n\n";

$js_fix = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(el) {
        if (!el.hasAttribute('data-bs-toggle')) {
            el.setAttribute('data-bs-toggle', 'dropdown');
            new bootstrap.Dropdown(el);
        }
    });
});
</script>
SCRIPT;

// Read existing footer HTML
$existing = get_config('core', 'additionalhtmlfooter') ?? '';

if (strpos($existing, 'data-toggle="dropdown"') !== false) {
    echo "  ⏭  Fix already present in additionalhtmlfooter — skipping.\n";
} else {
    set_config('additionalhtmlfooter', $existing . "\n" . $js_fix);
    echo "  ✅ Injected BS4→BS5 dropdown fix into additionalhtmlfooter\n";
}

// Purge caches
purge_all_caches();
echo "  ✅ All caches purged\n";

echo "\n═══════════════════════════════════════════════════\n";
echo "  DONE — category dropdowns should now open.\n";
echo "═══════════════════════════════════════════════════\n";
echo "\nVerify at: {$CFG->wwwroot}/course/index.php\n";
