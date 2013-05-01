<?php



error_reporting(E_ALL ^ E_NOTICE);



require_once '../library/Cervo/Cervo.php';
require_once '../library/Cervo/Config.php';



$config = \Cervo\Config::getInstance();

$config->setDocumentsDirectory(realpath(dirname(__FILE__)) . \DS);
$config->setApplicationDirectory(realpath($config->getDocumentsDirectory() . 'Application') . \DS);



\Cervo\Cervo::init();
