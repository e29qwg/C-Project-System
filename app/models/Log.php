<?php

class Log extends \Phalcon\Mvc\Model
{
    public $log_id;
    public $user_id;
    public $description;
    public $create_date;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnCreate()
    {
        $this->create_date = date('Y-m-d H:i:s');
    }
}

?>
