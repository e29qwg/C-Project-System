<?php

class ExcelFile extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $excel_id;

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var string
     */
    public $filename;

    /**
     *
     * @var string
     */
    public $file;

    /**
     *
     * @var string
     */
    public $common_name;

    /**
     *
     * @var integer
     */
    public $public;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('user_id', 'User', 'id', array('alias' => 'User'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'excel_file';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return ExcelFile[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return ExcelFile
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
