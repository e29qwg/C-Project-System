<?php

use Phalcon\Mvc\Model\Validator\Uniqueness;

class ProjectLevel extends \Phalcon\Mvc\Model
{
    public $project_level_id;
    public $project_level_name;
    public $coadvisor;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
