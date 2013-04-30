<?php



namespace Application\DefaultModule\Controllers;



use Cervo as _;



class Main extends _\Libraries\Controller
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
