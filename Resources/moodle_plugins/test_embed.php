<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');

ob_start();

// Check what the player embed page actually returns
echo "=== Vimeo Player Embed Page Content ===\n\n";

// Test 1: With localhost:8888 referer (what the browser sends)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://player.vimeo.com/video/1178285195");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: http://localhost:8888/']);
$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpcode\n";
echo "Response length: " . strlen($result) . "\n";
echo "Full response:\n";
echo $result . "\n";

echo "\n\n=== Test 2: No referer ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://player.vimeo.com/video/1178285195");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$result2 = curl_exec($ch);
$httpcode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpcode2\n";
echo "Response length: " . strlen($result2) . "\n";
// Just show title and any error messages
preg_match('/<title>(.*?)<\/title>/i', $result2, $title);
echo "Title: " . ($title[1] ?? 'unknown') . "\n";

// Check for privacy-related content
if (stripos($result2, 'privacy') !== false || stripos($result2, 'restricted') !== false) {
    echo "⚠ Privacy/restriction detected!\n";
}

// Test 3: Browser-style user agent with localhost:8888
echo "\n\n=== Test 3: Full browser simulation ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://player.vimeo.com/video/1178285195?autoplay=1&muted=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Referer: http://localhost:8888/mod/interactivevideo/view.php?id=17',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    'Origin: http://localhost:8888',
]);
$result3 = curl_exec($ch);
$httpcode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpcode3\n";
echo "Response length: " . strlen($result3) . "\n";
preg_match('/<title>(.*?)<\/title>/i', $result3, $title3);
echo "Title: " . ($title3[1] ?? 'unknown') . "\n";

// Check if it looks like a valid player page (contains "playerConfig" or similar)
if (stripos($result3, 'playerConfig') !== false || stripos($result3, 'window.playerConfig') !== false) {
    echo "✅ Player config found - video should play!\n";
} else if (stripos($result3, 'errorTitle') !== false) {
    preg_match('/errorTitle["\s:]+([^"<]+)/i', $result3, $errMatch);
    echo "⚠ Error found: " . ($errMatch[1] ?? 'unknown') . "\n";
} else {
    echo "Content preview: " . substr(strip_tags($result3), 0, 500) . "\n";
}

$output = ob_get_clean();
file_put_contents('/tmp/iv_embed_test.txt', $output);
echo "Written " . strlen($output) . " bytes\n";
