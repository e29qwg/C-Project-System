<?php

use Phalcon\Mvc\Model\Validator\Uniqueness;

class Project extends \Phalcon\Mvc\Model
{
    public $project_id;
    public $project_name;
    public $project_type;
    public $project_level_id;
    public $project_status;
    public $project_description;
    public $semeter_id;
    public $create_date;

    public function initialize()
    {
		$this->hasMany("project_id", "ProjectMap", "project_id");
		
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnCreate()
    {
        $this->project_status = 'Pending';
        $this->create_date = date('Y-m-d H:i:s');
    }
}

?>
