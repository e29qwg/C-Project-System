<?php

class ProjectLevel extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $project_level_id;

    /**
     *
     * @var string
     */
    public $project_level_name;

    /**
     *
     * @var integer
     */
    public $coadvisor;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('project_level_id', 'Enroll', 'project_level_id', array('alias' => 'Enroll'));
        $this->hasMany('project_level_id', 'Project', 'project_level_id', array('alias' => 'Project'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'project_level';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return ProjectLevel[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return ProjectLevel
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
