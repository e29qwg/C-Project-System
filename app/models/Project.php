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
     * @var string
     */
    public $create_date;

    public function beforeValidationOnCreate()
    {
        $this->create_date = date('Y-m-d H:i:s');
        $this->project_status = 'Pending';
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasMany('project_id', 'Progress', 'project_id', array('alias' => 'Progress'));
        $this->hasMany('project_id', 'ProjectMap', 'project_id', array('alias' => 'ProjectMap'));
        $this->hasMany('project_id', 'ScorePrepare', 'project_id', array('alias' => 'ScorePrepare'));
        $this->hasMany('project_id', 'ScoreProject', 'project_id', array('alias' => 'ScoreProject'));
        $this->hasMany('project_id', 'ReportComment', 'project_id', array('alias' => 'ReportComment'));
        $this->belongsTo('project_level_id', 'ProjectLevel', 'project_level_id', array('alias' => 'ProjectLevel'));
        $this->belongsTo('semester_id', 'Semester', 'semester_id', array('alias' => 'Semester'));
        $this->hasOne('project_id', 'RoomMap', 'project_id', array('alias' => 'RoomMap'));
    }

}
