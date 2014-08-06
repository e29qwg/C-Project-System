<?php

use Phalcon\Mvc\Model\Validator\Uniqueness;

class ProgressEvaluate extends \Phalcon\Mvc\Model
{
    public $progress_evaluate_id;
    public $progress_id;
    public $evaluation;
    public $comment;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
