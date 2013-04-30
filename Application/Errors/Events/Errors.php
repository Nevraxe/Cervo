<?php



use Cervo as _;



/** @var $this \Cervo\Libraries\Events */



$this->hook('core_pre_system', function($name, $params)
{

    set_error_handler([
        '\\Application\\ErrorsModule\\Controllers\\Errors',
        'errors_handler'
    ], error_reporting());

}, -999);
