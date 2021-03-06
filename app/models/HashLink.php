<?php

class HashLink extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $hash;

    /**
     *
     * @var string
     */
    public $link;

    /**
     *
     * @var string
     */
    public $expire_time;


    public function beforeValidationOnCreate()
    {
        $this->hash = \Phalcon\Text::random(Phalcon\Text::RANDOM_ALNUM, 20);
        $this->expire_time =  date('Y-m-d H:i:s', time() + 604800);
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('user_id', 'User', 'id', array('alias' => 'User'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'hash_link';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return HashLink[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return HashLink
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
