<?php
/**
 * Fix Interactive Video: Ensure all required fields are properly populated
 * and rebuild the interactivevideo record to match what the plugin expects.
 * 
 * Key findings from diagnosis:
 * 1. The 'video' field is empty (some plugin code paths may use this)
 * 2. 'displayasstartscreen' is empty/null (view.php references it at line 308/561)
 * 3. 'completionpercentage' is empty (view.php uses it at line 590)
 * 4. 'posterimage' is empty (may cause display issues)
 * 5. 'extendedcompletion' is empty (view.php passes it at line 600)
 * 6. displayoptions is missing some keys the view.php expects (beforecompletion, etc)
 * 
 * The fix populates all missing fields with proper defaults.
 */
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

echo "=== Fixing Interactive Video Record ===\n\n";

$iv = $DB->get_record('interactivevideo', ['id' => 1]);
if (!$iv) {
    echo "ERROR: IV record not found!\n";
    exit(1);
}

echo "Current state:\n";
echo "  videourl: {$iv->videourl}\n";
echo "  video: '" . ($iv->video ?: 'EMPTY') . "'\n";
echo "  displayasstartscreen: '" . ($iv->displayasstartscreen ?? 'NULL') . "'\n";
echo "  completionpercentage: '" . ($iv->completionpercentage ?? 'NULL') . "'\n";
echo "  posterimage: '" . ($iv->posterimage ?: 'EMPTY') . "'\n";
echo "  extendedcompletion: '" . ($iv->extendedcompletion ?: 'EMPTY') . "'\n";

// Fix 1: Set 'video' field to the vimeo URL (some code paths read this)
$iv->video = $iv->videourl;

// Fix 2: Set displayasstartscreen to 0 (don't show start screen - go straight to video)
$iv->displayasstartscreen = 0;

// Fix 3: Set completionpercentage to a reasonable default
$iv->completionpercentage = 80;

// Fix 4: Set extendedcompletion to empty JSON object
if (empty($iv->extendedcompletion)) {
    $iv->extendedcompletion = '{}';
}

// Fix 5: Update displayoptions to include ALL fields the view.php expects
$opts = json_decode($iv->displayoptions, true) ?: [];

// Ensure all required display option keys exist
$defaults = [
    'darkmode' => 0,
    'distractionfreemode' => 1,
    'useoriginalvideocontrols' => 0,
    'hidemainvideocontrols' => 0,
    'hideinteractions' => 0,
    'disablechapternavigation' => 0,
    'showdescriptiononheader' => 1,
    'squareposterimage' => false,
    'courseindex' => 0,
    'allowdeleteprogress' => 0,
    'autoplay' => 1,
    'pauseonblur' => 1,
    'autohidecontrols' => 0,
    'preventseeking' => 0,
    'preventskipping' => 0,
    'passwordprotected' => 0,
    'theme' => '',
    'usecustomposterimage' => 0,
];

foreach ($defaults as $key => $default) {
    if (!isset($opts[$key])) {
        $opts[$key] = $default;
    }
}

// Add beforecompletion/aftercompletion appearance settings
// These are required by view.php lines 498-528
if (!isset($opts['beforecompletion'])) {
    $appearance = [
        'useoriginalvideocontrols' => 0,
        'hidemainvideocontrols' => 0,
        'interactionbar' => 1,
        'progressbar' => 1,
        'chaptertoggle' => 1,
        'chaptertitle' => 1,
        'timestamp' => 1,
        'rewind' => 0,
        'forward' => 0,
        'captions' => 1,
        'playbackrate' => 1,
        'quality' => 1,
        'mute' => 1,
        'share' => 1,
        'expand' => 1,
        'fullscreen' => 1,
    ];
    $opts['beforecompletion'] = $appearance;
    $opts['aftercompletion'] = $appearance;
    
    $behavior = [
        'preventskipping' => 0,
        'preventseeking' => 0,
        'disableinteractionclick' => 0,
        'disableinteractionclickuntilcompleted' => 0,
    ];
    $opts['beforecompletionbehavior'] = $behavior;
    $opts['aftercompletionbehavior'] = $behavior;
}

$iv->displayoptions = json_encode($opts);
$iv->timemodified = time();

// Apply the update
$DB->update_record('interactivevideo', $iv);

echo "\nFixes applied:\n";
echo "  ✅ video = '{$iv->video}'\n";
echo "  ✅ displayasstartscreen = {$iv->displayasstartscreen}\n";
echo "  ✅ completionpercentage = {$iv->completionpercentage}\n";
echo "  ✅ extendedcompletion = '{$iv->extendedcompletion}'\n";
echo "  ✅ displayoptions updated with all required keys\n";

// Also create the context record if missing
$cm = $DB->get_record('course_modules', ['id' => 17]);
$ctx = $DB->get_record('context', ['contextlevel' => 70, 'instanceid' => 17]);
if (!$ctx) {
    echo "\n⚠ Creating missing context for cmid 17...\n";
    context_module::instance(17);
    echo "  ✅ Context created\n";
} else {
    echo "\n✅ Context exists: id={$ctx->id}\n";
}

// Rebuild Moodle caches
echo "\nRebuilding caches...\n";
purge_all_caches();
echo "  ✅ Caches purged\n";

echo "\n=== Verification ===\n";
$iv2 = $DB->get_record('interactivevideo', ['id' => 1]);
echo "  videourl: {$iv2->videourl}\n";
echo "  video: {$iv2->video}\n";
echo "  source: {$iv2->source}\n";
echo "  type: {$iv2->type}\n";
echo "  displayasstartscreen: {$iv2->displayasstartscreen}\n";
echo "  completionpercentage: {$iv2->completionpercentage}\n";
echo "  displayoptions keys: " . implode(', ', array_keys(json_decode($iv2->displayoptions, true))) . "\n";

echo "\n🎯 Done! Try accessing http://localhost:8888/mod/interactivevideo/view.php?id=17\n";
echo "Also test standalone: http://localhost:8888/vimeo_test.html\n";
