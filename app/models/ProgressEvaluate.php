<?php

class ProgressEvaluate extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $progress_evaluate_id;

    /**
     *
     * @var integer
     */
    public $progress_id;

    /**
     *
     * @var string
     */
    public $evaluation;

    /**
     *
     * @var string
     */
    public $comment;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('progress_id', 'Progress', 'progress_id', array('alias' => 'Progress'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'progress_evaluate';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return ProgressEvaluate[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return ProgressEvaluate
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
