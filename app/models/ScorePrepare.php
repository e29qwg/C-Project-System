<?php

class ScorePrepare extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $score_id;

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
     * @var double
     */
    public $report_advisor;

    /**
     *
     * @var double
     */
    public $present_advisor;

    /**
     *
     * @var double
     */
    public $report_coadvisor;

    /**
     *
     * @var double
     */
    public $present_coadvisor;

    /**
     *
     * @var double
     */
    public $progress_report;

    /**
     *
     * @var string
     */
    public $grade;

    /**
     *
     * @var integer
     */
    public $is_midterm;

    /**
     *
     * @var string
     */
    public $edit_date;

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->belongsTo('user_id', 'User', 'id', array('alias' => 'User'));
        $this->belongsTo('project_id', 'Project', 'project_id', array('alias' => 'Project'));
    }

    /**
     * Returns table name mapped in the model.
     *
     * @return string
     */
    public function getSource()
    {
        return 'score_prepare';
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return ScorePrepare[]
     */
    public static function find($parameters = null)
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return ScorePrepare
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}
