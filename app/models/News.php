<?php

class News extends \Phalcon\Mvc\Model
{
    public $id;
    public $news;
    public $create_date;

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
