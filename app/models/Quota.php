<?php

class Quota extends \Phalcon\Mvc\Model
{
	public $id;
	public $advisor_id;
	public $quota_pp;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
