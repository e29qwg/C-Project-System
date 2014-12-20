<?php

class SendEmail extends \Phalcon\Mvc\Model
{
    public $id;
    public $to;
    public $subject;

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
