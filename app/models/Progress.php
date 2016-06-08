<?php

class Progress extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $progress_id;

    /**
     *
     * @var integer
     */
    public $user_id;

    /**
     *
     * @var integer
     */
    public $project_id;

    /**
     *
     * @var string
     */
    public $progress_finish;

    /**
     *
     * @var string
     */
    public $progress_working;

    /**
     *
     * @var string
     */
    public $progress_todo;

    /**
     *
     * @var string
     */
    public $progress_summary;

    /**
     *
     * @var string
     */
    public $progress_target;

    /**
     *
     * @var string
     */
    public $edit_date;

    /**
     *
     * @var string
     */
    public $create_date;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->hasOne('progress_id', 'ProgressEvaluate', 'progress_id', array('alias' => 'ProgressEvaluate'));
        $this->belongsTo('project_id', 'Project', 'project_id', array('alias' => 'Project'));
        $this->belongsTo('user_id', 'User', 'id', array('alias' => 'User'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'progress';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return Progress[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return Progress
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
