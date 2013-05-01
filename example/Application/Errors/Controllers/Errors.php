<?php



namespace Application\ErrorsModule\Controllers;



use Cervo\Cervo as _;
use Cervo;



class Errors extends Cervo\Libraries\Controller
{
    static public function exceptions_handler($e)
    {
        $controller = &_::getController('Errors');

        if ($e instanceof _\Libraries\Exceptions\RouteNotFoundException)
        {
            $controller->Error404Method();
        }
        else
        {
            $controller->Error500Method();
        }

        exit();
    }

    static public function errors_handler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        _::getController('Errors')->Error500Method();

        exit();
    }

    public function Error404Method($Args = array())
    {
    	header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found", true, 404);
        _::getLibrary('Cervo/Events')->fire('module_errors_404', $Args);
    }

    public function Error500Method($Args = array())
    {
    	header($_SERVER['SERVER_PROTOCOL'] . " 500 Internal Server Error", true, 500);
        _::getLibrary('Cervo/Events')->fire('module_errors_500', $Args);
    }

}
