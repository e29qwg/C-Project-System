<?php

class Semester extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $semester_id;

    /**
     *
     * @var integer
     */
    public $semester_term;

    /**
     *
     * @var integer
     */
    public $semester_year;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('semester_id', 'Enroll', 'semester_id', array('alias' => 'Enroll'));
        $this->hasMany('semester_id', 'Project', 'semester_id', array('alias' => 'Project'));
        $this->hasMany('semester_id', 'UserCurrentSemester', 'semester_id', array('alias' => 'UserCurrentSemester'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'semester';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Semester[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Semester
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
