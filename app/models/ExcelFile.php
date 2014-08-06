<?php

class ExcelFile extends \Phalcon\Mvc\Model
{
    public $excel_id;
    public $user_id;
    public $filename;
    public $file;
    public $common_name;
    public $public;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }
}

?>
