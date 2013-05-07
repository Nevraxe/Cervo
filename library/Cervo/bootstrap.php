<?php



if (!defined('DS'))
    define('DS', \DIRECTORY_SEPARATOR);



$current_directory = realpath(dirname(__FILE__)) . \DS;



require $current_directory . 'Cervo.php';



spl_autoload_register('\Cervo::autoload');
