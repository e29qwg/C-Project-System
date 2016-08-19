<?php

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;

class User extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $title;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $tel;

    /**
     *
     * @var string
     */
    public $email;

    /**
     *
     * @var string
     */
    public $facebook;

    /**
     *
     * @var string
     */
    public $type;

    /**
     *
     * @var integer
     */
    public $advisor_group;

    /**
     *
     * @var integer
     */
    public $work_load;

    /**
     *
     * @var string
     */
    public $interesting;

    /**
     *
     * @var integer
     */
    public $active;

    /**
     *
     * @var integer
     */
    public $activate_code;

    /**
     *
     * @var string
     */
    public $last_login;

    /**
     *
     * @var string
     */
    public $create_date;

    public $ignoreCheck;

    public function isComplete()
    {
        if ($this->active == 0 || empty($this->title) || empty($this->name) || empty($this->tel) || empty($this->email))
            return false;
        return true;
    }

    public function beforeValidationOnCreate()
    {
        $this->interesting = 'ยังไม่ระบุ';
        $this->work_load = 0;
        $this->advisor_group = 0;
        $this->create_date = date('Y-m-d H:i:s');
        $this->last_login = date('Y-m-d H:i:s');
        $this->active = 0;
    }

    /**
     * Validations and business logic
     *
     * @return boolean
     */
    public function validation()
    {
        $validator = new Validation();

        if (!$this->ignoreCheck)
        {
            $validator->add('email', new Email(array(
                'message' => 'Email Invalid'
            )));
        }

        return $this->validate($validator);
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->ignoreCheck = false;

        $this->hasMany('id', 'ExcelFile', 'user_id', array('alias' => 'ExcelFile'));
        $this->hasMany('id', 'HashLink', 'user_id', array('alias' => 'HashLink'));
        $this->hasMany('id', 'Log', 'user_id', array('alias' => 'Log'));
        $this->hasMany('id', 'Notification', 'user_id', array('alias' => 'Notification'));
        $this->hasMany('id', 'Progress', 'user_id', array('alias' => 'Progress'));
        $this->hasMany('id', 'ProjectMap', 'user_id', array('alias' => 'ProjectMap'));
        $this->hasMany('id', 'Quota', 'advisor_id', array('alias' => 'Quota'));
        $this->hasMany('id', 'ScorePrepare', 'user_id', array('alias' => 'ScorePrepare'));
        $this->hasMany('id', 'ScoreProject', 'user_id', array('alias' => 'ScoreProject'));
        $this->hasMany('id', 'UserCurrentSemester', 'user_id', array('alias' => 'UserCurrentSemester'));
        $this->hasOne('id', 'Room', 'user_id', array('alias' => 'Room'));
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return User[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return User
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'user';
    }

}
