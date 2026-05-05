<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

// Check scorm related tables and their columns
$tables = ['scorm_scoes', 'scorm_scoes_data', 'scorm_scoes_track', 'scorm_aicc_session'];
foreach ($tables as $t) {
    try {
        $cols = $DB->get_columns($t);
        echo "$t:\n";
        foreach ($cols as $n => $c) {
            echo "  $n ({$c->type})\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "$t: TABLE NOT FOUND\n\n";
    }
}

// Also check what scorm_scoes records exist
echo "=== scorm_scoes for scorm instance 1 ===\n";
$scoes = $DB->get_records('scorm_scoes', ['scorm' => 1]);
echo "  Count: " . count($scoes) . "\n";
foreach ($scoes as $sco) {
    echo "  SCO {$sco->id}: {$sco->title}\n";
}
