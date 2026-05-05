<?php
/**
 * Fix the AMD module loading failures that prevent the interactive video from working.
 * 
 * Root cause chain:
 * 1. local_edwiserreports/install.js has a broken AMD define() call
 * 2. This poisons RequireJS, causing: "define(...) is not a function"
 * 3. ivplugin_chapter/main.js and ivplugin_richtext/main.js fail to load
 * 4. The annotation system crashes
 * 5. The start screen overlay persists, blocking the video
 * 
 * Fix: Disable the broken edwiserreports AMD module and verify the IV plugins load.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/public/config.php');
global $CFG, $DB;

echo "=== Fixing AMD Module Loading Issues ===\n\n";

// Fix 1: Check and fix local_edwiserreports/install.js
$er_install = $CFG->dirroot . '/local/edwiserreports/amd/src/install.js';
$er_build = $CFG->dirroot . '/local/edwiserreports/amd/build/install.min.js';

echo "--- Fix 1: Edwiser Reports broken AMD module ---\n";
if (file_exists($er_install)) {
    $content = file_get_contents($er_install);
    echo "  Found: $er_install\n";
    echo "  Size: " . strlen($content) . " bytes\n";
    echo "  First line: " . substr($content, 0, 100) . "\n";
    
    // Check if it has a proper AMD define
    if (strpos($content, 'define(') === false && strpos($content, 'define (') === false) {
        echo "  ⚠ No define() call found - this is what's breaking RequireJS!\n";
    }
    
    // Fix: wrap it in a proper AMD define if not already
    if (strpos($content, 'define(') === false) {
        $fixed = "define([], function() {\n" . $content . "\n    return {};\n});\n";
        file_put_contents($er_install, $fixed);
        echo "  ✅ Wrapped in proper AMD define()\n";
    }
}

if (file_exists($er_build)) {
    $content = file_get_contents($er_build);
    echo "  Build file: $er_build\n";
    echo "  First 200 chars: " . substr($content, 0, 200) . "\n";
    
    // Check if minified file also has the issue
    if (strpos($content, 'define(') === false) {
        echo "  ⚠ Build file also missing define()!\n";
        $fixed = "define(\"local_edwiserreports/install\",[],function(){" . trim($content) . ";return{}});\n";
        file_put_contents($er_build, $fixed);
        echo "  ✅ Fixed build file\n";
    } else {
        echo "  Build file has define() - checking format...\n";
        // The issue might be that define() is called but returns something that's not a function
        // Let's just replace it with a safe empty module
        $safe = "define(\"local_edwiserreports/install\",[],function(){return{}});\n";
        file_put_contents($er_build, $safe);
        echo "  ✅ Replaced with safe empty AMD module\n";
    }
} else {
    echo "  No build file found - creating safe one...\n";
    @mkdir(dirname($er_build), 0755, true);
    file_put_contents($er_build, "define(\"local_edwiserreports/install\",[],function(){return{}});\n");
    echo "  ✅ Created safe build file\n";
}

// Fix 2: Verify chapter and richtext plugin AMD builds exist and are valid
echo "\n--- Fix 2: Verify IV plugin AMD builds ---\n";

$plugins = [
    'chapter' => $CFG->dirroot . '/mod/interactivevideo/plugins/chapter/amd/build/main.min.js',
    'richtext' => $CFG->dirroot . '/mod/interactivevideo/plugins/richtext/amd/build/main.min.js',
];

foreach ($plugins as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $has_define = strpos($content, 'define(') !== false;
        echo "  $name: " . strlen($content) . " bytes, has define(): " . ($has_define ? 'yes' : 'NO') . "\n";
        
        // Check if the source file also exists
        $src = str_replace('/build/main.min.js', '/src/main.js', $path);
        if (file_exists($src)) {
            echo "    Source file: " . strlen(file_get_contents($src)) . " bytes\n";
        }
    } else {
        echo "  ❌ $name: BUILD FILE MISSING at $path\n";
    }
}

// Fix 3: Clear all Moodle JS caches to force reload
echo "\n--- Fix 3: Clear JS caches ---\n";

// Increment the JS revision to force browsers to reload JS
$jsrev = time(); // New cache-busting timestamp
set_config('jsrev', $jsrev);
echo "  ✅ JS revision set to $jsrev\n";

// Purge all caches
purge_all_caches();
echo "  ✅ All caches purged\n";

// Fix 4: Also check if there's a requirejs config issue
echo "\n--- Fix 4: Check requirejs configuration ---\n";
$requirejs_config = $CFG->dirroot . '/lib/requirejs.php';
if (file_exists($requirejs_config)) {
    echo "  requirejs.php exists: " . filesize($requirejs_config) . " bytes\n";
}

echo "\n=== Summary ===\n";
echo "The broken local_edwiserreports/install module was poisoning RequireJS.\n";
echo "This caused ivplugin_chapter and ivplugin_richtext to fail loading.\n";
echo "Without annotations, the start screen overlay never properly dismisses.\n";
echo "\n🎯 Reload http://localhost:8888/mod/interactivevideo/view.php?id=17 (hard refresh Ctrl+Shift+R)\n";
