<?php



// A small shortcut that is used througout Cervo

if (!defined('DS'))
    define('DS', \DIRECTORY_SEPARATOR);



$current_directory = realpath(dirname(__FILE__)) . \DS;



require $current_directory . 'Libraries/Config.php';
require $current_directory . 'Cervo.php';
require $current_directory . 'CervoTools.php';



spl_autoload_register('\Cervo::autoload');
