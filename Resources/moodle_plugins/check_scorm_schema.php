<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB;

echo "=== scorm table columns ===\n";
$cols = $DB->get_columns('scorm');
foreach ($cols as $name => $col) {
    $nn = $col->not_null ? 'NOT NULL' : 'nullable';
    $def = $col->has_default ? "default={$col->default_value}" : 'no_default';
    echo "  $name ({$col->type}) $nn $def\n";
}
