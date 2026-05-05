<?php
/**
 * Simulate rendering view.php and capture any PHP errors/warnings.
 * This uses output buffering and error handling to catch issues.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;

ob_start();

echo "=== Testing page render for cmid=17 ===\n\n";

// Check if we can access the page via curl from localhost
echo "--- Accessing view page via internal curl ---\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:80/mod/interactivevideo/view.php?id=17");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, "");
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Login first to get session cookie
$login_ch = curl_init();
curl_setopt($login_ch, CURLOPT_URL, "http://localhost:80/login/index.php");
curl_setopt($login_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($login_ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($login_ch, CURLOPT_HEADER, true);
curl_setopt($login_ch, CURLOPT_COOKIEJAR, '/tmp/moodle_cookies.txt');
curl_setopt($login_ch, CURLOPT_COOKIEFILE, '/tmp/moodle_cookies.txt');
$login_page = curl_exec($login_ch);
curl_close($login_ch);

// Extract logintoken
preg_match('/name="logintoken"\s+value="([^"]+)"/', $login_page, $token_match);
$logintoken = $token_match[1] ?? '';
echo "Login token: " . ($logintoken ? 'found' : 'NOT FOUND') . "\n";

// Login with credentials
$login_ch2 = curl_init();
curl_setopt($login_ch2, CURLOPT_URL, "http://localhost:80/login/index.php");
curl_setopt($login_ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($login_ch2, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($login_ch2, CURLOPT_POST, true);
curl_setopt($login_ch2, CURLOPT_POSTFIELDS, http_build_query([
    'username' => 'admin',
    'password' => 'Admin123!',
    'logintoken' => $logintoken,
]));
curl_setopt($login_ch2, CURLOPT_COOKIEJAR, '/tmp/moodle_cookies.txt');
curl_setopt($login_ch2, CURLOPT_COOKIEFILE, '/tmp/moodle_cookies.txt');
$login_result = curl_exec($login_ch2);
$login_code = curl_getinfo($login_ch2, CURLINFO_HTTP_CODE);
curl_close($login_ch2);
echo "Login result: HTTP $login_code\n";

// Now access the IV page
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:80/mod/interactivevideo/view.php?id=17");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/moodle_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/moodle_cookies.txt');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$page = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "\nIV Page result: HTTP $httpcode\n";
echo "Effective URL: $effective_url\n";
echo "Page length: " . strlen($page) . " bytes\n";

// Check for PHP errors/warnings in the page
if (preg_match_all('/(Fatal error|Warning|Notice|Error|Exception):([^\n<]{0,500})/i', $page, $errors)) {
    echo "\n⚠ PHP Errors found:\n";
    foreach ($errors[0] as $err) {
        echo "  " . trim($err) . "\n";
    }
} else {
    echo "✅ No PHP errors detected in page output.\n";
}

// Check if the page contains the player div
if (strpos($page, 'id="player"') !== false || strpos($page, 'id="video-wrapper"') !== false) {
    echo "✅ Player div found in page\n";
} else {
    echo "❌ Player div NOT found in page!\n";
}

// Check if the JS init call is present
if (preg_match('/js_call_amd.*viewannotation.*init/s', $page) || 
    strpos($page, 'mod_interactivevideo/viewannotation') !== false) {
    echo "✅ viewannotation AMD call found\n";
} else {
    echo "❌ viewannotation AMD call NOT found!\n";
}

// Check for the video URL being passed to JS
if (strpos($page, 'vimeo.com/1178285195') !== false) {
    echo "✅ Vimeo URL found in page source\n";
} else {
    echo "❌ Vimeo URL NOT found in page source!\n";
}

// Extract the AMD init params
if (preg_match('/require.*mod_interactivevideo\/viewannotation.*?init.*?\[(.*?)\]/s', $page, $amd_match)) {
    echo "\nAMD init params: " . substr($amd_match[1], 0, 500) . "\n";
}

// Check if doptions textarea exists
if (preg_match('/<textarea id="doptions"[^>]*>(.*?)<\/textarea>/s', $page, $dopt_match)) {
    echo "\n✅ Display options textarea found\n";
    echo "Content: " . substr($dopt_match[1], 0, 300) . "\n";
} else {
    echo "\n❌ Display options textarea NOT found!\n";
}

// Check for the player template
if (strpos($page, 'video-wrapper') !== false) {
    echo "✅ video-wrapper element found\n";
} else {
    echo "❌ video-wrapper NOT found!\n";
}

if (strpos($page, 'start-screen') !== false) {
    echo "✅ start-screen element found\n";
} else {
    echo "❌ start-screen NOT found\n";
}

if (strpos($page, 'annotation-canvas') !== false) {
    echo "✅ annotation-canvas element found\n";
} else {
    echo "❌ annotation-canvas NOT found\n";
}

// Save the full page for analysis
file_put_contents('/tmp/iv_page_render.html', $page);
echo "\nFull page saved to /tmp/iv_page_render.html (" . strlen($page) . " bytes)\n";

$output = ob_get_clean();
file_put_contents('/tmp/iv_render_test.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
