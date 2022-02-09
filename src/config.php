<?php  // Moodle configuration file
  
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = strval(getenv("MYSQL_ADDRESS"));
$CFG->dbname    = getenv("MYSQL_DATABASE");
$CFG->dbuser    = getenv("MYSQL_USERNAME");
$CFG->dbpass    = getenv("MYSQL_PASSWORD");
$CFG->prefix    = 'mdl_';

$CFG->wwwroot   = getenv("HOST_DOMAIN");
$CFG->dataroot  = '/var/moodledata';
$CFG->localcachedir = '/var/localcache';
$CFG->admin     = 'admin';
$CFG->sslproxy = true;

$CFG->directorypermissions = 0777;
$CFG->alternative_file_system_class = '\tool_objectfs\s3_file_system';

if(getenv("REDIS_SESSION") == "enable"){
$CFG->session_handler_class = '\core\session\redis';
$CFG->session_redis_host = getenv("REDIS_ADDRESS");
$CFG->session_redis_port = 6379;  // Optional.
$CFG->session_redis_database = 0;  // Optional, default is db 0.
$CFG->session_redis_prefix = 'mod_us_'; // Optional, default is don't set one.
$CFG->session_redis_acquire_lock_timeout = 7200;
$CFG->session_redis_lock_expire = 7200;
}

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
