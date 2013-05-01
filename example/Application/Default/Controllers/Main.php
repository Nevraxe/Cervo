<?php



namespace Application\DefaultModule\Controllers;



use Cervo\Cervo as _;
use Cervo;



class Main extends Cervo\Libraries\Controller
{
    public function IndexMethod($args = [])
    {
        $test = _::getModel('Default/Test');
        $test->setTest('Hello World!');

    	$view = _::getView('Default/MainIndex');
        $view->setTest($test);
        $view->render();
    }
}
