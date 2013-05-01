<?php



use Cervo\Cervo as _;



/** @var $this \Cervo\Libraries\Events */



$this->Hook('core_pre_system', function($name, $params)
{

    $events = &_::getLibrary('Cervo/Events');

    $events->register('module_errors_404');
    $events->register('module_errors_500');

}, -999);
