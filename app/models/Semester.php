<?php

class Semester extends \Phalcon\Mvc\Model
{
    public $semeter_id;
    public $semester_term;
    public $semester_year;

    public function initialize()
    {
        $this->useDynamicUpdate(true);

        $this->hasMany('semester_id', 'Project', 'semester_id', array(
            'foreignKey' => array(
                'action' => \Phalcon\Mvc\Model\Relation::ACTION_RESTRICT,
                'message' => 'มีโครงงานอ้างอิงไม่สามารถลบได้'
            )
        ));
    }

    public function validation()
    {
        return $this->validationHasFailed() != true;
    }
}

?>
