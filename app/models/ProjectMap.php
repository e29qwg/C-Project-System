<?php

class ProjectMap extends \Phalcon\Mvc\Model
{
    public $project_map_id;
    public $user_id;
    public $project_id;
    public $map_type;

    public function initialize()
    {
        $this->belongsTo("project_id", "Project", "project_id");
        $this->useDynamicUpdate(true);
    }
}

?>
