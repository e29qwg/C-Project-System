<?php

class Quota extends \Phalcon\Mvc\Model
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
    public $advisor_id;

    /**
     *
     * @var integer
     */
    public $quota_pp;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('advisor_id', 'User', 'id', array('alias' => 'User'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'quota';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Quota[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Quota
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
