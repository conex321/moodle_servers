<?php
/**
 * Inject a JavaScript error logger into the interactivevideo view page.
 * This adds a small <script> tag to the page footer that captures all JS errors
 * and sends them to our logging endpoint.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $CFG;

// Create a small PHP file that injects the error logger via Moodle's locallib
$inject_code = <<<'EOT'
<script>
// IV Debug Logger - captures all JS issues
(function() {
    var logEndpoint = '/jslog.php';
    var log = function(type, msg) {
        try {
            fetch(logEndpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: type, message: String(msg).substring(0, 2000)})
            });
        } catch(e) {}
    };

    // Capture all errors
    window.addEventListener('error', function(e) {
        log('ERROR', e.message + ' at ' + e.filename + ':' + e.lineno + ':' + e.colno);
    });

    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        log('PROMISE_REJECT', String(e.reason));
    });

    // Capture AMD require errors
    if (typeof require !== 'undefined' && require.onError) {
        var origOnError = require.onError;
        require.onError = function(err) {
            log('AMD_ERROR', err.message + ' | requireType: ' + err.requireType + ' | modules: ' + JSON.stringify(err.requireModules));
            if (origOnError) origOnError(err);
        };
    }

    // Log key IV events
    document.addEventListener('iv:playerError', function(e) {
        log('IV_PLAYER_ERROR', JSON.stringify(e.detail));
    });
    document.addEventListener('iv:playerLoaded', function(e) {
        log('IV_PLAYER_LOADED', 'Player loaded successfully');
    });
    document.addEventListener('iv:playerReady', function(e) {
        log('IV_PLAYER_READY', 'Player is ready');
    });
    document.addEventListener('iv:autoplayBlocked', function(e) {
        log('IV_AUTOPLAY_BLOCKED', 'Autoplay was blocked');
    });

    // Log when the page finishes loading
    window.addEventListener('load', function() {
        log('PAGE_LOAD', 'Page fully loaded');

        // Check if critical elements exist
        setTimeout(function() {
            var player = document.getElementById('player');
            var wrapper = document.getElementById('video-wrapper');
            var startScreen = document.getElementById('start-screen');
            var loader = document.getElementById('background-loading');
            var iframe = document.querySelector('#player iframe');

            log('DOM_CHECK', JSON.stringify({
                hasPlayerDiv: !!player,
                hasVideoWrapper: !!wrapper,
                hasStartScreen: !!startScreen,
                hasLoader: !!loader,
                loaderVisible: loader ? (loader.style.display !== 'none' && !loader.classList.contains('d-none')) : false,
                hasIframe: !!iframe,
                iframeSrc: iframe ? iframe.src : 'no iframe',
                playerInnerHTML: player ? player.innerHTML.substring(0, 500) : 'no player div',
            }));
        }, 5000);
    });

    log('INIT', 'Error logger initialized on ' + location.href);
})();
</script>
EOT;

// Inject this into the page by modifying the plugin's view.php output
// We'll use Moodle's additional HTML footer setting
$DB = $GLOBALS['DB'];

// Set the additionalhtmlfooter config
$key = 'additionalhtmlfooter';
$existing = get_config('core', $key);
$marker = '<!-- IV_DEBUG_LOGGER -->';

if (strpos($existing, $marker) === false) {
    $new_value = $existing . "\n" . $marker . "\n" . $inject_code . "\n" . $marker;
    set_config($key, $new_value);
    echo "✅ Injected JS error logger into Moodle footer.\n";
    echo "   View errors: http://localhost:8888/jslog.php\n";
    echo "   The logger captures: JS errors, AMD errors, IV player events, DOM state.\n";
} else {
    echo "Logger already injected.\n";
}

// Clear the JS error log
file_put_contents('/tmp/js_errors.log', '');
echo "Cleared previous log.\n";

// Purge caches so the change takes effect
purge_all_caches();
echo "Caches purged.\n";

echo "\n=== NEXT STEPS ===\n";
echo "1. Open http://localhost:8888/mod/interactivevideo/view.php?id=17 in your browser\n";
echo "2. Wait 10 seconds for the page to load\n";
echo "3. I will check http://localhost:8888/jslog.php for the captured errors\n";
