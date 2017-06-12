<?php

class Project extends \Phalcon\Mvc\Model
{
    public $project_id;
    public $project_name;
    public $project_type;
    public $project_level_id;
    public $project_status;
    public $project_description;
    public $semester_id;
    public $create_date;
    public $store_option;
    public static $STORE_IN_NEXT_PROJECT = 'use_in_next_project';
    public static $STORE_MOVE_TO_ADVISOR = 'move_to_advisor';

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
