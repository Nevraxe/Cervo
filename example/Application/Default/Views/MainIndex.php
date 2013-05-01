<?php



namespace Application\DefaultModule\Views;



use Cervo\Cervo as _;
use Cervo;



class MainIndex extends Cervo\Libraries\View
{
    private $test = null;

    public function render()
	{
        _::getTemplate('Default/MainIndex')->assign([
            'Test' => $this->test
        ])->render();
	}

	public function &setTest(\Application\DefaultModule\Models\Test $test)
	{
	    $this->test = &$test;
        return $this;
    }
}
