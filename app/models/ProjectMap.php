<?php

use Phalcon\Mvc\Model\Validator\Uniqueness;

class ProjectMap extends \Phalcon\Mvc\Model
{
    public $project_map_id;
    public $user_id;
    public $project_id;
    public $map_type;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
