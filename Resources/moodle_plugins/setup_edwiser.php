<?php
/**
 * Edwiser License Activation & White-Label Branding Script
 * 
 * Activates all Edwiser license keys via the EDD API,
 * removes Edwiser branding, and hides help buttons.
 * 
 * Run: docker exec moodle-app php /tmp/setup_edwiser.php
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
require_once($CFG->libdir . '/adminlib.php');

// Fake a user agent for CLI
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Moodle CLI';
}

echo "==============================================\n";
echo "  Edwiser License Activation & White-Label\n";
echo "==============================================\n\n";

// ============================================================
// PART 1: ACTIVATE REMUI LICENSE
// ============================================================
echo "--- [1/4] Activating RemUI License ---\n";

$remui_key = '21780b5a09a914c28216f5411c0a92d3';
$remui_slug = 'remui';
$remui_plugin_name = 'Edwiser RemUI';
$remui_store_url = 'https://edwiser.org/check-update';

// Use Moodle's curl class
require_once($CFG->libdir . '/filelib.php');

function activate_edwiser_license($key, $plugin_name, $store_url, $plugin_component, $slug) {
    global $CFG, $DB;
    
    $curl = new curl();
    $curl->setopt([
        'CURLOPT_RETURNTRANSFER' => 1,
        'CURLOPT_TIMEOUT' => 30,
        'CURLOPT_SSL_VERIFYPEER' => false
    ]);
    
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        $curl->setopt(['CURLOPT_IPRESOLVE' => CURL_IPRESOLVE_V4]);
    }
    
    $resp = $curl->post($store_url, [
        'edd_action'      => 'activate_license',
        'license'         => $key,
        'item_name'       => urlencode($plugin_name),
        'current_version' => '3.3.0',
        'url'             => urlencode($CFG->wwwroot),
    ]);
    
    $data = json_decode($resp);
    
    if ($data === null) {
        echo "  ERROR: No response from license server\n";
        echo "  Raw response: " . substr($resp, 0, 200) . "\n";
        return false;
    }
    
    echo "  Server response: " . json_encode($data) . "\n";
    
    // Store license data in Moodle config
    $edd_key = 'edd_' . $slug . '_license_key';
    $edd_status = 'edd_' . $slug . '_license_status';
    $edd_action = 'licenseactionperformed';
    $wdm_trans = 'wdm_' . $slug . '_license_trans';
    $edd_purchase_from = 'edd_' . $slug . '_purchase_from';
    $wdm_product_site = 'wdm_' . $slug . '_product_site';
    
    set_config($edd_key, $key, $plugin_component);
    set_config($edd_purchase_from, $slug, $plugin_component);
    
    // Determine status
    $status = 'invalid';
    if (isset($data->license)) {
        $status = $data->license; // 'valid', 'invalid', etc.
    }
    if (isset($data->error) && $data->error == 'expired') {
        $status = 'expired';
    }
    
    set_config($edd_status, $status, $plugin_component);
    set_config($edd_action, true, $plugin_component);
    
    // Set transient (valid for 7 days if active)
    $trans_time = ($status == 'valid') ? time() + (86400 * 7) : time() + 86400;
    set_config($wdm_trans, serialize([$status, $trans_time]), $plugin_component);
    
    // Store product site
    if (isset($data->renew_link) && !empty($data->renew_link)) {
        set_config($wdm_product_site, $data->renew_link, $plugin_component);
    } else {
        set_config($wdm_product_site, 'https://edwiser.org', $plugin_component);
    }
    
    echo "  Status: $status\n";
    return $status;
}

// Activate RemUI
$status = activate_edwiser_license(
    $remui_key,
    'Edwiser RemUI',
    'https://edwiser.org/check-update',
    'theme_remui',
    'remui'
);
echo "  RemUI License: $status\n\n";

// ============================================================
// PART 2: ACTIVATE EDWISER FORMS PRO LICENSE
// ============================================================
echo "--- [2/4] Activating Edwiser Forms Pro License ---\n";

// Edwiser Forms uses local_edwiserform as plugin component
// Need to check the actual slug used by the forms plugin
$forms_key = '2232f8b87911f8ebb6f2c2f0f3b88181';

// Read the forms license controller to find the slug
$forms_lc_file = '/var/www/html/public/local/edwiserform/classes/license_controller.php';
if (file_exists($forms_lc_file)) {
    $forms_content = file_get_contents($forms_lc_file);
    // Extract plugin slug
    if (preg_match("/plugin_slug\s*=\s*'([^']+)'/", $forms_content, $m)) {
        $forms_slug = $m[1];
    } else {
        $forms_slug = 'suspended_edwiserform'; // Common fallback based on Edwiser naming
    }
    if (preg_match("/plugin_item_name\s*=\s*'([^']+)'/", $forms_content, $m)) {
        $forms_name = $m[1];
    } else {
        $forms_name = 'Edwiser Forms Pro';
    }
    if (preg_match("/store_url\s*=\s*'([^']+)'/", $forms_content, $m)) {
        $forms_store = $m[1];
    } else {
        $forms_store = 'https://edwiser.org/check-update';
    }
    echo "  Detected slug: $forms_slug, name: $forms_name\n";
    
    $status = activate_edwiser_license(
        $forms_key,
        $forms_name,
        $forms_store,
        'local_edwiserform',
        $forms_slug
    );
    echo "  Forms Pro License: $status\n\n";
} else {
    echo "  WARNING: Edwiser Forms not installed, skipping.\n\n";
}

// ============================================================
// PART 3: ACTIVATE EDWISER REPORTS LICENSE (if installed)
// ============================================================
echo "--- [3/4] Activating Edwiser Reports License ---\n";

$reports_key = '063c8930009106079111a593c2a939b6';
$reports_lc_file = '/var/www/html/public/local/edwiserreports/classes/controller/license.php';
if (file_exists($reports_lc_file)) {
    $reports_content = file_get_contents($reports_lc_file);
    if (preg_match("/plugin_slug\s*=\s*'([^']+)'/", $reports_content, $m)) {
        $reports_slug = $m[1];
    } else {
        $reports_slug = 'suspended_edwiserreports';
    }
    if (preg_match("/plugin_item_name\s*=\s*'([^']+)'/", $reports_content, $m)) {
        $reports_name = $m[1];
    } else {
        $reports_name = 'Edwiser Reports Pro';
    }
    if (preg_match("/store_url\s*=\s*'([^']+)'/", $reports_content, $m)) {
        $reports_store = $m[1];
    } else {
        $reports_store = 'https://edwiser.org/check-update';
    }
    echo "  Detected slug: $reports_slug, name: $reports_name\n";
    
    $status = activate_edwiser_license(
        $reports_key,
        $reports_name,
        $reports_store,
        'local_edwiserreports',
        $reports_slug
    );
    echo "  Reports License: $status\n\n";
} else {
    echo "  WARNING: Edwiser Reports not found, skipping.\n\n";
}

// ============================================================
// PART 4: ACTIVATE EDWISER RAPIDGRADER LICENSE (if installed)
// ============================================================
echo "--- [4/4] Checking RapidGrader ---\n";

$grader_key = 'fab3ede418a4c7652daf2d6034bf5578';
$grader_lc_file = '/var/www/html/public/blocks/edwiser_grader/classes/license_controller.php';
if (file_exists($grader_lc_file)) {
    $grader_content = file_get_contents($grader_lc_file);
    if (preg_match("/plugin_slug\s*=\s*'([^']+)'/", $grader_content, $m)) {
        $grader_slug = $m[1];
    } else {
        $grader_slug = 'edwiser_grader';
    }
    if (preg_match("/plugin_item_name\s*=\s*'([^']+)'/", $grader_content, $m)) {
        $grader_name = $m[1];
    } else {
        $grader_name = 'Edwiser RapidGrader';
    }
    if (preg_match("/store_url\s*=\s*'([^']+)'/", $grader_content, $m)) {
        $grader_store = $m[1];
    } else {
        $grader_store = 'https://edwiser.org/check-update';
    }
    echo "  Detected slug: $grader_slug, name: $grader_name\n";
    
    $status = activate_edwiser_license(
        $grader_key,
        $grader_name,
        $grader_store,
        'block_edwiser_grader',
        $grader_slug
    );
    echo "  RapidGrader License: $status\n\n";
} else {
    echo "  WARNING: RapidGrader not found, skipping.\n\n";
}

// ============================================================
// PART 5: REMOVE EDWISER BRANDING & HIDE HELP
// ============================================================
echo "--- Applying White-Label Branding ---\n";

$custom_css = <<<'CSS'
/* ========================================
   WHITE-LABEL: Remove all Edwiser Branding
   ======================================== */

/* Hide "Powered by Edwiser" footer text and links */
.powered-by-edwiser,
.edwiser-footer-branding,
a[href*="edwiser.org"],
.footer-poweredby,
.poweredby,
.edw-footer-bottom-text a[href*="edwiser"],
.edw-footer-bottom a[href*="edwiser"],
.footer-content-debugging a[href*="edwiser"],
.edw-copyright a[href*="edwiser"],
a.footer-link[href*="edwiser"] {
    display: none !important;
}

/* Hide help/docs floating button */
.helplink,
.help-button,
.moodledocslink,
.floating-help-btn,
a.helptoggleropen,
#help-toggler,
.edwiser-help-btn,
.remui-help-support,
.edw-help-support,
#remui-help-support,
.remui-support-icon,
button[data-action="help"],
.edw-bug-report,
#edw-bug-report,
.edw_bug_report,
.wdm-help-support {
    display: none !important;
}

/* Hide any Edwiser branding badges/links */
.edwiser-badge,
.edwiser-branding,
[class*="edwiser-brand"],
.edw-brand-logo {
    display: none !important;
}

/* Hide Information Center / license nag notices */
.edwiser-license-notice,
.activation-notice,
.remui-license-notice,
.license-nag,
.edw-license-nag,
#remui-license-notice {
    display: none !important;
}

/* Hide feedback collection popup */
.edwiser-feedback-modal,
.remui-feedback-modal,
#edwiser-feedback-modal,
.edwiser-star-rating {
    display: none !important;
}

/* Hide Edwiser usage tracking notices */
.edwiser-tracking-notice,
.remui-usage-notice {
    display: none !important;
}

/* Hide product notification banner */
.edwiser-product-notification,
.remui-product-notification,
#remui-product-notification {
    display: none !important;
}
CSS;

// Set the custom CSS in RemUI theme settings
set_config('customcss', $custom_css, 'theme_remui');
echo "  Custom CSS applied (Edwiser branding hidden)\n";

// Disable powered by setting if it exists
set_config('poweredby', 0, 'theme_remui');
echo "  Powered by setting disabled\n";

// Disable usage tracking
set_config('enableusagetracking', 0, 'theme_remui');
echo "  Usage tracking disabled\n";

// Disable feedback collection
set_config('enablefeedback', 0, 'theme_remui');
echo "  Feedback collection disabled\n";

// Disable product notifications
set_config('enableproductnotification', 0, 'theme_remui');
echo "  Product notifications disabled\n\n";

// ============================================================
// PART 6: PURGE CACHES
// ============================================================
echo "--- Purging All Caches ---\n";
purge_all_caches();
echo "  All caches purged successfully\n\n";

// ============================================================
// PART 7: VERIFICATION
// ============================================================
echo "--- Verification ---\n";

// Check stored license statuses
$plugins_to_check = [
    'theme_remui' => 'edd_remui_license_status',
    'local_edwiserform' => null, // Will be determined
    'local_edwiserreports' => null,
];

$remui_status = get_config('theme_remui', 'edd_remui_license_status');
echo "  RemUI license status: " . ($remui_status ?: 'not set') . "\n";

$remui_key_stored = get_config('theme_remui', 'edd_remui_license_key');
echo "  RemUI license key stored: " . ($remui_key_stored ? 'YES' : 'NO') . "\n";

$custom_css_stored = get_config('theme_remui', 'customcss');
echo "  Custom CSS applied: " . (strlen($custom_css_stored) > 100 ? 'YES (' . strlen($custom_css_stored) . ' chars)' : 'NO') . "\n";

echo "\n==============================================\n";
echo "  Setup Complete!\n";
echo "==============================================\n";
echo "  Next: Visit http://localhost:8888 to verify\n";
echo "  the clean, professional appearance.\n";
echo "==============================================\n";
