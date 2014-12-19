<?php

class Enroll extends \Phalcon\Mvc\Model
{
    public $id;
    public $student_id;
    public $project_level_id;
    public $semester_id;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnCreate()
    {
        $this->create_date = date('Y-m-d H:i:s');
    }
}

?>
