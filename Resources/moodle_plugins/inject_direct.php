<?php
/**
 * Inject JavaScript error logger directly into the interactivevideo view.php.
 * The embedded layout skips additionalhtmlfooter, so we must inject here.
 */
define('CLI_SCRIPT', true);

$viewfile = '/var/www/html/public/mod/interactivevideo/view.php';
$content = file_get_contents($viewfile);

// Check if already injected
if (strpos($content, 'IV_DIRECT_DEBUG') !== false) {
    echo "Logger already injected into view.php\n";
    exit(0);
}

$logger_code = <<<'JSLOGGER'
// <!-- IV_DIRECT_DEBUG -->
echo '<script>
(function() {
    var logEndpoint = "/jslog.php";
    var log = function(type, msg) {
        try {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", logEndpoint, true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.send(JSON.stringify({type: type, message: String(msg).substring(0, 2000)}));
        } catch(e) { console.log("Logger error:", e); }
    };

    window.addEventListener("error", function(e) {
        log("JS_ERROR", e.message + " at " + e.filename + ":" + e.lineno + ":" + e.colno);
    });
    window.addEventListener("unhandledrejection", function(e) {
        log("PROMISE_REJECT", String(e.reason));
    });

    // Monitor AMD require errors
    if (typeof require !== "undefined") {
        var origOnError = require.onError;
        require.onError = function(err) {
            log("AMD_ERROR", err.message + " | modules: " + JSON.stringify(err.requireModules || []));
            if (origOnError) origOnError(err);
        };
    }

    // Monitor IV-specific events
    var ivEvents = ["iv:playerError","iv:playerLoaded","iv:playerReady","iv:autoplayBlocked"];
    ivEvents.forEach(function(evt) {
        document.addEventListener(evt, function(e) {
            log("IV_EVENT_" + evt, JSON.stringify(e.detail || {}));
        });
    });

    // Check page state after a delay
    window.addEventListener("load", function() {
        log("PAGE_LOADED", window.location.href);
        setTimeout(function() {
            var p = document.getElementById("player");
            var w = document.getElementById("video-wrapper");
            var s = document.getElementById("start-screen");
            var l = document.getElementById("background-loading");
            var iframes = document.querySelectorAll("iframe");
            var iframeInfo = [];
            iframes.forEach(function(f) { iframeInfo.push(f.src || "no-src"); });
            log("DOM_STATE", JSON.stringify({
                player: !!p, wrapper: !!w, startScreen: !!s, 
                loader: !!l, loaderVisible: l ? window.getComputedStyle(l).display : "N/A",
                iframes: iframeInfo,
                playerHTML: p ? p.innerHTML.substring(0, 1000) : "none"
            }));
        }, 8000);
    });

    log("LOGGER_INIT", "Debug logger active on " + window.location.href);
})();
</script>';
// <!-- /IV_DIRECT_DEBUG -->
JSLOGGER;

// Find the footer line and inject before it
$target = "echo \$OUTPUT->footer();";
if (strpos($content, $target) !== false) {
    $content = str_replace($target, $logger_code . "\n" . $target, $content);
    file_put_contents($viewfile, $content);
    echo "✅ Logger injected directly into view.php before footer().\n";
} else {
    echo "❌ Could not find footer() call in view.php!\n";
    // Try alternate target
    $target2 = 'echo $OUTPUT->footer()';
    echo "Looking for: $target2\n";
    if (strpos($content, $target2) !== false) {
        echo "Found alternate pattern!\n";
    }
}

// Also move jslog.php to the correct webroot
if (!file_exists('/var/www/html/public/jslog.php')) {
    copy('/var/www/html/jslog.php', '/var/www/html/public/jslog.php');
    echo "✅ Copied jslog.php to /var/www/html/public/\n";
} else {
    echo "jslog.php already exists in public/\n";
}

// Clear log
file_put_contents('/tmp/js_errors.log', '');
echo "Log cleared.\n";

echo "\nNow reload http://localhost:8888/mod/interactivevideo/view.php?id=17\n";
echo "Then check: docker exec moodle-app cat /tmp/js_errors.log\n";
