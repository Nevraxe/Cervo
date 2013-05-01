<?php



namespace Application\DefaultModule\Models;



use Cervo\Cervo as _;
use Cervo;



class Test extends Cervo\Libraries\Model
{
    private $test = '';

    public function setTest($test)
    {
        $this->test = $test;
    }

    public function getTest()
    {
        return $this->test;
    }
}
