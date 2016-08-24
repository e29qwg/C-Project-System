<?php

class RoomBacklist extends \Phalcon\Mvc\Model
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
    public $reason;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $create_date;

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'room_backlist';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return RoomBacklist[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return RoomBacklist
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
