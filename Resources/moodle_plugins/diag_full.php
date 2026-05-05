<?php
/**
 * Full diagnostic of Interactive Video module - writes to file for easy reading.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;

$out = "";

$out .= "=== FULL INTERACTIVE VIDEO DIAGNOSTIC ===\n";
$out .= "Date: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Find all interactive video instances
$out .= "=== ALL interactivevideo records ===\n";
$ivs = $DB->get_records('interactivevideo');
foreach ($ivs as $iv) {
    $out .= "  ID: {$iv->id}\n";
    $out .= "  Name: {$iv->name}\n";
    $out .= "  Source: '{$iv->source}'\n";
    $out .= "  Type: '{$iv->type}'\n";
    $out .= "  VideoURL: '{$iv->videourl}'\n";
    $out .= "  Video (raw): '{$iv->video}'\n";
    $out .= "  StartTime: {$iv->starttime}\n";
    $out .= "  EndTime: {$iv->endtime}\n";
    $out .= "  CompletionPct: {$iv->completionpercentage}\n";
    $out .= "  Grade: {$iv->grade}\n";
    $out .= "  Course: {$iv->course}\n";
    
    // Display options (critical for player config)
    $out .= "  DisplayOptions (raw): " . ($iv->displayoptions ?? 'NULL') . "\n";
    $opts = json_decode($iv->displayoptions ?? '', true);
    if ($opts) {
        $out .= "  DisplayOptions (parsed):\n";
        foreach ($opts as $k => $v) {
            $out .= "    $k: " . json_encode($v) . "\n";
        }
    }
    $out .= "\n";
}

// 2. Course module record
$out .= "=== Course Modules for mod_interactivevideo ===\n";
$module = $DB->get_record('modules', ['name' => 'interactivevideo']);
if ($module) {
    $out .= "  Module ID: {$module->id}\n";
    $out .= "  Visible: {$module->visible}\n";
    $cms = $DB->get_records('course_modules', ['module' => $module->id]);
    foreach ($cms as $cm) {
        $out .= "  CM ID: {$cm->id}, Instance: {$cm->instance}, Visible: {$cm->visible}, Course: {$cm->course}\n";
        
        // Context
        $ctx = $DB->get_record('context', ['contextlevel' => 70, 'instanceid' => $cm->id]);
        if ($ctx) {
            $out .= "    Context: ID={$ctx->id}, Path={$ctx->path}\n";
        } else {
            $out .= "    ⚠ NO CONTEXT RECORD!\n";
        }
    }
} else {
    $out .= "  ⚠ Module 'interactivevideo' not found in modules table!\n";
}

// 3. Items/annotations
$out .= "\n=== interactivevideo_items (all) ===\n";
$items = $DB->get_records('interactivevideo_items', null, 'id ASC');
$out .= "Total items: " . count($items) . "\n";
$type_counts = [];
foreach ($items as $item) {
    $type_counts[$item->type] = ($type_counts[$item->type] ?? 0) + 1;
}
$out .= "Types breakdown: " . json_encode($type_counts) . "\n\n";

// Show first 3 of each type
$shown = [];
foreach ($items as $item) {
    $t = $item->type;
    $shown[$t] = ($shown[$t] ?? 0) + 1;
    if ($shown[$t] <= 2) {
        $out .= "  [{$t}] ID={$item->id} cmid={$item->cmid} timestamp={$item->timestamp}\n";
        $out .= "    title: {$item->title}\n";
        $out .= "    annotationid: {$item->annotationid}\n";
        $out .= "    hascompletion: {$item->hascompletion}\n";
        $out .= "    contextid: {$item->contextid}\n";
        $out .= "    displayoptions (first 300): " . substr($item->displayoptions ?? '', 0, 300) . "\n";
        $out .= "    content (first 300): " . substr($item->content ?? '', 0, 300) . "\n\n";
    }
}

// 4. Check plugin files
$out .= "=== Plugin file structure ===\n";
$base = $CFG->dirroot . '/mod/interactivevideo';
$out .= "Plugin dir: $base\n";
$out .= "Exists: " . (is_dir($base) ? 'YES' : 'NO') . "\n\n";

// Check for player files (these handle video playback)
$player_dirs = [
    '/player/',
    '/amd/src/',
    '/amd/build/',
    '/classes/',
    '/classes/local/',
];

foreach ($player_dirs as $dir) {
    $full = $base . $dir;
    if (is_dir($full)) {
        $files = scandir($full);
        $out .= "  $dir:\n";
        foreach ($files as $f) {
            if ($f !== '.' && $f !== '..') {
                $out .= "    $f\n";
            }
        }
    }
}

// 5. Check for Vimeo-specific player
$out .= "\n=== Vimeo player check ===\n";
$vimeo_paths = [
    '/player/vimeo.js',
    '/amd/src/player/vimeo.js', 
    '/amd/build/player/vimeo.min.js',
    '/player/vimeo/amd/src/player.js',
];
foreach ($vimeo_paths as $p) {
    $full = $base . $p;
    $out .= "  $p: " . (file_exists($full) ? 'EXISTS (' . filesize($full) . ' bytes)' : 'NOT FOUND') . "\n";
}

// Search more broadly
$out .= "\n  Searching for vimeo-related files:\n";
$search = shell_exec("find $base -iname '*vimeo*' -type f 2>/dev/null");
$out .= "  " . ($search ?: "  (none found)") . "\n";

// 6. Check subplugins configuration
$out .= "\n=== Sub-plugins ===\n";
$subplugins_file = $base . '/db/subplugins.json';
if (file_exists($subplugins_file)) {
    $out .= "  subplugins.json: " . file_get_contents($subplugins_file) . "\n";
}
$subplugins_file2 = $base . '/db/subplugins.php';
if (file_exists($subplugins_file2)) {
    $out .= "  subplugins.php exists\n";
    $out .= "  Content: " . file_get_contents($subplugins_file2) . "\n";
}

// Check for content type plugins (ivplugin sub-plugins)
$out .= "\n=== Installed ivplugin sub-plugins ===\n";
try {
    $installed = core_plugin_manager::instance()->get_installed_plugins('ivplugin');
    if ($installed) {
        foreach ($installed as $name => $version) {
            $out .= "  ivplugin_$name (v$version)\n";
        }
    } else {
        $out .= "  (none installed)\n";
    }
} catch (Exception $e) {
    $out .= "  Error: " . $e->getMessage() . "\n";
}

// 7. Check ivplugin directories
$out .= "\n=== ivplugin directories ===\n";
$ivplugin_base = $CFG->dirroot . '/ivplugin';
if (is_dir($ivplugin_base)) {
    $dirs = scandir($ivplugin_base);
    foreach ($dirs as $d) {
        if ($d !== '.' && $d !== '..' && is_dir("$ivplugin_base/$d")) {
            $out .= "  $d/\n";
            $subfiles = scandir("$ivplugin_base/$d");
            foreach ($subfiles as $sf) {
                if ($sf !== '.' && $sf !== '..') {
                    $out .= "    $sf\n";
                }
            }
        }
    }
}

// Also check mod/interactivevideo/interaction/ or plugins/
$alt_plugins = $base . '/plugins';
if (is_dir($alt_plugins)) {
    $out .= "\n  mod/interactivevideo/plugins/:\n";
    $dirs = scandir($alt_plugins);
    foreach ($dirs as $d) {
        if ($d !== '.' && $d !== '..') $out .= "    $d\n";
    }
}

// 8. Check the 'source' field - this determines which player to use
$out .= "\n=== Source/Type analysis ===\n";
foreach ($ivs as $iv) {
    $out .= "IV#{$iv->id}: source='{$iv->source}', type='{$iv->type}'\n";
    
    // Try to understand what 'source' values the plugin expects
    // Check the mod_form or lib.php for valid source values
}

// 9. Check mod_form for valid source types
$out .= "\n=== Valid source types from plugin ===\n";
$mod_form = $base . '/mod_form.php';
if (file_exists($mod_form)) {
    $content = file_get_contents($mod_form);
    // Look for source-type mapping
    preg_match_all('/[\'"]source[\'"].*?=>\s*[\'"]([^\'"]+)[\'"]/i', $content, $matches);
    if (!empty($matches[1])) {
        $out .= "  Source values found: " . implode(', ', $matches[1]) . "\n";
    }
    // Check for vimeo references
    preg_match_all('/vimeo/i', $content, $vmatches);
    $out .= "  Vimeo references in mod_form: " . count($vmatches[0]) . "\n";
}

// 10. Check lib.php for player initialization
$out .= "\n=== lib.php vimeo references ===\n";
$lib = $base . '/lib.php';
if (file_exists($lib)) {
    $content = file_get_contents($lib);
    preg_match_all('/vimeo/i', $content, $vmatches);
    $out .= "  Vimeo references in lib.php: " . count($vmatches[0]) . "\n";
}

// 11. Check JavaScript AMD modules
$out .= "\n=== AMD src files ===\n";
$amd_src = $base . '/amd/src';
if (is_dir($amd_src)) {
    $walk = function($dir, $prefix = '') use (&$walk, &$out) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = "$dir/$item";
            if (is_dir($path)) {
                $out .= "  {$prefix}$item/\n";
                $walk($path, $prefix . '  ');
            } else {
                $out .= "  {$prefix}$item (" . filesize($path) . " bytes)\n";
            }
        }
    };
    $walk($amd_src);
}

// 12. Check version info
$out .= "\n=== Plugin version ===\n";
$version_file = $base . '/version.php';
if (file_exists($version_file)) {
    $content = file_get_contents($version_file);
    preg_match('/\$plugin->version\s*=\s*(\d+)/', $content, $m);
    $out .= "  Version: " . ($m[1] ?? 'unknown') . "\n";
    preg_match('/\$plugin->release\s*=\s*[\'"]([^\'"]+)/', $content, $m);
    $out .= "  Release: " . ($m[1] ?? 'unknown') . "\n";
    preg_match('/\$plugin->requires\s*=\s*(\d+)/', $content, $m);
    $out .= "  Requires: " . ($m[1] ?? 'unknown') . "\n";
}

// Write output
file_put_contents('/tmp/iv_diag.txt', $out);
echo "Diagnostic written to /tmp/iv_diag.txt (" . strlen($out) . " bytes)\n";
echo substr($out, 0, 500) . "\n...\n";
