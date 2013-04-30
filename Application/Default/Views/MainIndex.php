<?php



namespace Application\DefaultModule\Views;



use Cervo as _;



class MainIndex extends _\Libraries\View
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
