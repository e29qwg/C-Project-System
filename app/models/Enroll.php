<?php

class Enroll extends \Phalcon\Mvc\Model
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
    public $student_id;

    /**
     *
     * @var integer
     */
    public $project_level_id;

    /**
     *
     * @var integer
     */
    public $semester_id;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('project_level_id', 'ProjectLevel', 'project_level_id', array('alias' => 'ProjectLevel'));
        $this->belongsTo('semester_id', 'Semester', 'semester_id', array('alias' => 'Semester'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'enroll';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Enroll[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Enroll
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
