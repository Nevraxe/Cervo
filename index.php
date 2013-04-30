<?php



// Configurable elements

$error_reporting = E_ALL ^ E_NOTICE;
$override_display_errors = true;
$display_errors = 1; // override_display_errors needs to be true for this one to work

$core_directory = 'Cervo';
$application_directory = 'Application';



// Defining the global constants

if (!ini_get('display_errors') && $override_display_errors === true)
    ini_set('display_errors', $display_errors);

error_reporting($error_reporting);



// System
define('EXT', '.php');
define('DS', DIRECTORY_SEPARATOR);

// System paths
define('DOCROOT', realpath(dirname(__FILE__)) . DS);
define('CPATH', realpath(DOCROOT . $core_directory) . DS);
define('CLIBRARIESPATH', realpath(CPATH . 'Libraries') . DS);
define('APATH', realpath(DOCROOT . $application_directory) . DS);

// Modules
define('MODULENAMESPACESUFFIX', 'Module');

// Events
define('SUBEVENTSPATH', 'Events' . DS);

// Controllers
define('METHODSUFFIX', 'Method');
define('SUBCONTROLLERSPATH', 'Controllers' . DS);

// Models
define('SUBMODELSPATH', 'Models' . DS);

// Views
define('SUBVIEWSPATH', 'Views' . DS);

// Classes
define('SUBLIBRARIESPATH', 'Libraries' . DS);

// Templates
define('SUBTEMPLATESPATH', 'Templates' . DS);



// Cleanup

unset($error_reporting, $override_display_errors, $core_directory, $application_directory);



// We start the core

require_once CPATH . 'Cervo' . EXT;
\Cervo::init();
