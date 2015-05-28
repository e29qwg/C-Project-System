<?php

use \Phalcon\Mvc\Model\Validator\Email;
use \Phalcon\Mvc\Model\Validator\Numericality;
use \Phalcon\Mvc\Model\Validator\StringLength;
use \Phalcon\Mvc\Model\Validator\Regex;

class User extends \Phalcon\Mvc\Model
{
    public $id;
    public $user_id;
    public $title;
    public $name;
    public $tel;
    public $email;
    public $facebook;
    public $type;
    public $advisor_group;
    public $work_load;
    public $interesting;
    public $last_login;
    public $create_date;

    private $checkProfile;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
        $this->checkProfile = false;
    }

    public function validation()
    {
        return $this->validationHasFailed() != true;
    }

    public function turnOnProfileCheck()
    {
        $this->checkProfile = true;
    }

    public function beforeValidationOnUpdate()
    {
        if ($this->checkProfile)
        {
            $this->validate(new Regex(
                array(
                    'field' => 'facebook',
                    'pattern' => '(^(?!http).*$)',
                    'message' => 'Facebook invalid format.'
                )
            ));

            $this->validate(new StringLength(
                array(
                    'field' => 'tel',
                    'max' => 10,
                    'min' => 9
                )
            ));

            $this->validate(new Numericality(
                array(
                    'field' => 'tel',
                    'message' => 'Tel is invalid'
                )
            ));

            $this->validate(new Email(
                array(
                    'field' => 'email',
                    'message' => 'Email must have a valid e-mail format'
                )
            ));
        }
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
