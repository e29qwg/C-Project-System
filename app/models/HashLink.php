<?php

class HashLink extends \Phalcon\Mvc\Model
{
    public $id;
    public $user_id;
    public $hash;
    public $link;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnCreate()
    {
    }
}

?>
