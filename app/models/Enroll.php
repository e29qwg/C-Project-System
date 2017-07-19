<?php

class Enroll extends \Phalcon\Mvc\Model
{
    public $id;
    public $student_id;
    public $project_level_id;
    public $semester_id;

    public function initialize()
    {
        $this->belongsTo('project_level_id', 'ProjectLevel', 'project_level_id', array('alias' => 'ProjectLevel'));
        $this->belongsTo('semester_id', 'Semester', 'semester_id', array('alias' => 'Semester'));
    }
}
