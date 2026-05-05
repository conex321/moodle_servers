<?php
/**
 * Check Moodle wwwroot config and Vimeo API domain settings to identify
 * the root cause of the video playback failure.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $CFG, $DB;

ob_start();

echo "=== Moodle Configuration ===\n";
echo "wwwroot: " . $CFG->wwwroot . "\n";
echo "httpswwwroot: " . ($CFG->httpswwwroot ?? 'not set') . "\n";
echo "altcacheconfigpath: " . ($CFG->altcacheconfigpath ?? 'not set') . "\n";
echo "\n";

// Check what the browser would see as the page URL
echo "=== Page URL the player would see ===\n";
echo "Base URL: " . $CFG->wwwroot . "/mod/interactivevideo/view.php?id=17\n";
echo "\n";

// Check Vimeo API for video privacy/domain settings
echo "=== Vimeo API Video Privacy Check ===\n";

// We need the Vimeo access token from the upload script
// Check vimeo_result.json for the video ID
$vimeo_result = '/data/Grade_01/Mathematics/Algebra/Learning_Activity_01/video/vimeo_result.json';
if (!file_exists($vimeo_result)) {
    $vimeo_result = '/tmp/vimeo_result_check.json';
}

// Try to check via oEmbed with referer
$video_url = 'https://vimeo.com/1178285195';

echo "Testing oEmbed with domain referers:\n\n";

// Test 1: No referer
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://vimeo.com/api/oembed.json?url=" . urlencode($video_url));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  No referer: HTTP $httpcode\n";
$data = json_decode($result, true);
if ($data) {
    echo "    title: " . ($data['title'] ?? 'n/a') . "\n";
    echo "    video_id: " . ($data['video_id'] ?? 'n/a') . "\n";
    echo "    domain_status_code: " . ($data['domain_status_code'] ?? 'not present') . "\n";
    echo "    error: " . ($data['error'] ?? 'none') . "\n";
    echo "    html (first 200): " . substr($data['html'] ?? '', 0, 200) . "\n";
}

// Test 2: With localhost referer
echo "\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://vimeo.com/api/oembed.json?url=" . urlencode($video_url));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: http://localhost:8888/mod/interactivevideo/view.php?id=17']);
$result2 = curl_exec($ch);
$httpcode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  Referer localhost:8888: HTTP $httpcode2\n";
$data2 = json_decode($result2, true);
if ($data2) {
    echo "    domain_status_code: " . ($data2['domain_status_code'] ?? 'not present') . "\n";
}

// Test 3: With localhost (no port) referer
echo "\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://vimeo.com/api/oembed.json?url=" . urlencode($video_url) . "&domain=localhost");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: http://localhost/']);
$result3 = curl_exec($ch);
$httpcode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  Referer localhost (no port) with domain param: HTTP $httpcode3\n";
$data3 = json_decode($result3, true);
if ($data3) {
    echo "    domain_status_code: " . ($data3['domain_status_code'] ?? 'not present') . "\n";
}

// Now test the actual player embed URL format
echo "\n=== Testing Vimeo Player iframe accessibility ===\n";
$embed_url = "https://player.vimeo.com/video/1178285195";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $embed_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: http://localhost:8888/']);
$result4 = curl_exec($ch);
$httpcode4 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  Player embed (referer localhost:8888): HTTP $httpcode4\n";
echo "  Response length: " . strlen($result4) . " bytes\n";
// Check if it contains error messages
if (stripos($result4, 'privacy') !== false || stripos($result4, 'block') !== false 
    || stripos($result4, 'restrict') !== false || stripos($result4, 'not allowed') !== false) {
    preg_match('/<title>(.*?)<\/title>/i', $result4, $title);
    echo "  ⚠ Possible restriction! Page title: " . ($title[1] ?? 'unknown') . "\n";
    // Look for error divs
    preg_match_all('/(private|restrict|block|embed|domain|allow)[^<]{0,200}/i', $result4, $errors);
    echo "  Error context: " . implode(" | ", array_unique($errors[0] ?? [])) . "\n";
}

// Check if the config.php has correct wwwroot
echo "\n=== config.php wwwroot check ===\n";
$config = file_get_contents('/var/www/html/config.php');
preg_match("/\\\$CFG->wwwroot\s*=\s*['\"]([^'\"]+)['\"]/", $config, $m);
echo "  config.php wwwroot: " . ($m[1] ?? 'not found') . "\n";

$output = ob_get_clean();
file_put_contents('/tmp/iv_domain_check.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
