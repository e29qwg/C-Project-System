<?php

class Log extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $log_id;

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $description;

    /**
     *
     * @var string
     */
    public $create_date;

    public function beforeValidationOnCreate()
    {
        $this->create_date = date('Y-m-d H:i:s');
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('user_id', 'User', 'id', array('alias' => 'User'));
    }

}
