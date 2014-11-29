<?php

class Semester extends \Phalcon\Mvc\Model
{
    public $semeter_id;
    public $semester_term;
    public $semester_year;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
