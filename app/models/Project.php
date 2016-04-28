<?php

class Project extends \Phalcon\Mvc\Model
{
    public $project_id;
    public $project_name;
    public $project_type;
    public $project_level_id;
    public $project_status;
    public $project_description;
    public $semeter_id;
    public $project_farm;
    public $create_date;

    public function initialize()
    {
        $this->useDynamicUpdate(true);

        $this->belongsTo('semester_id', 'Semester', 'semester_id', array(
           'foreignKey' => array(
               'message' => 'Semester not exists',
               'action' => \Phalcon\Mvc\Model\Relation::ACTION_CASCADE
           )
        ));
    }

    public function beforeValidationOnCreate()
    {
        $this->project_status = 'Pending';
        $this->create_date = date('Y-m-d H:i:s');
    }

    public function validation()
    {
        return $this->validationHasFailed() != true;
    }
}

?>
