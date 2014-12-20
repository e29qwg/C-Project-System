<?php

class User extends \Phalcon\Mvc\Model
{
    public $id;
    public $user_id;
    public $title;
    public $name;
    public $email;
    public $facebook;
    public $type;
    public $advisor_group;
    public $work_load;
    public $interesting;
    public $last_login;
    public $create_date;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnCreate()
    {
        $this->interesting = 'ยังไม่ระบุ';
        $this->work_load = 0;
        $this->advisor_group = 0;
        $this->create_date = date('Y-m-d H:i:s');
        $this->last_login = date('Y-m-d H:i:s');
    }
}

?>
