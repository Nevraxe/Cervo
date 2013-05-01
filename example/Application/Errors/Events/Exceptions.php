<?php



/** @var $this \Cervo\Libraries\Events */



$this->hook('core_pre_system', function($name, $params)
{

    set_exception_handler([
        '\\Application\\ErrorsModule\\Controllers\\Errors',
        'exceptions_handler'
    ]);

}, -999);
