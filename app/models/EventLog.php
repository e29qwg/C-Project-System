<?php

class EventLog extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=10, nullable=false)
     */
    public $id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $username;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $event;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $system;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $date;

    public function beforeValidationOnCreate()
    {
        $this->date = date('Y-m-d H:i:s');
    }


    public function initialize()
    {
        $this->setConnectionService('dbLog');
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'event_log';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return EventLog[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return EventLog
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
