<?php



namespace Application\DefaultModule\Models;



use Cervo as _;



class Test extends _\Libraries\Model
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
