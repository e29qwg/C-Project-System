<?php

use \Phalcon\Mvc\Model\Validator\Inclusionin;

class Notification extends \Phalcon\Mvc\Model
{
    public $id;
    public $user_id;
    public $noption;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function validation()
    {
        $this->validate(new Inclusionin(
                array(
                    'field' => 'noption',
                    'message' => 'Option invalid',
                    'domain' => array(
                        'project_update',
                        'progress_update'
                    )
                ))
        );

        return $this->validationHasFailed() != true;
    }
}

?>
