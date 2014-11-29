<?php

class Settings extends \Phalcon\Mvc\Model
{
    public $id;
    public $name;
    public $value;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
