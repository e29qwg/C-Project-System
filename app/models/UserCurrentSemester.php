<?php

class UserCurrentSemester extends \Phalcon\Mvc\Model
{
    public $id;
    public $user_id;
    public $semester_id;

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
