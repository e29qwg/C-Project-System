<?php

class Progress extends \Phalcon\Mvc\Model
{
    public $progress_id;
    public $user_id;
    public $project_id;
    public $progress_finish;
    public $progress_working;
    public $progress_todo;
    public $progress_summary;
    public $progress_target;
    public $create_date;
    public $edit_date;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnCreate()
    {
        $this->create_date = date('Y-m-d H:i:s');
    }

    public function beforeValidationOnUpdate()
    {
        $this->edit_date = date('Y-m-d H:i:s');
    }
}

?>
