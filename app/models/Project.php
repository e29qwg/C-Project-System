<?php

class Project extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $project_id;

    /**
     *
     * @var string
     */
    public $project_name;

    /**
     *
     * @var string
     */
    public $project_type;

    /**
     *
     * @var integer
     */
    public $project_level_id;

    /**
     *
     * @var string
     */
    public $project_status;

    /**
     *
     * @var string
     */
    public $project_description;

    /**
     *
     * @var integer
     */
    public $semester_id;

    /**
     *
     * @var integer
     */
    public $project_farm;

    /**
     *
     * @var string
     */
    public $create_date;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('project_id', 'Progress', 'project_id', array('alias' => 'Progress'));
        $this->hasMany('project_id', 'ProjectMap', 'project_id', array('alias' => 'ProjectMap'));
        $this->hasMany('project_id', 'ScorePrepare', 'project_id', array('alias' => 'ScorePrepare'));
        $this->hasMany('project_id', 'ScoreProject', 'project_id', array('alias' => 'ScoreProject'));
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
        return 'project';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Project[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Project
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
