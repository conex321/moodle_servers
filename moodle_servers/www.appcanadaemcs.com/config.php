<?php  // Moodle configuration file — PRODUCTION (5.78.128.44)

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'mariadb';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodleuser';
$CFG->dbpass    = 'moodlepass';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport'    => 3306,
    'dbsocket'  => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot = 'https://app.canadaemcs.com';
$CFG->dataroot  = '/var/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0770;

// forcelogin managed via DB (set by configure_remui_full.php)
// $CFG->forcelogin = false;

// Set to true when HTTPS reverse proxy is added
$CFG->sslproxy = true;
$CFG->debug = 0;
$CFG->debugdisplay = 0;

$CFG->lock_factory = "\core\lock\db_record_lock_factory";
require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
