<?php

class ScorePrepare extends \Phalcon\Mvc\Model
{
    public $score_id;
    public $user_id;
    public $project_id;
    public $report_advisor;
    public $present_advisor;
    public $report_coadvisor;
    public $present_coadvisor;
    public $progress_report;
    public $grade;
    public $is_midterm;
    public $edit_date;

    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function beforeValidationOnUpdate()
    {
        $sum = $this->report_advisor;
        $sum += $this->present_advisor;
        $sum += $this->report_coadvisor;
        $sum += $this->present_coadvisor;
        $sum += $this->progress_report;

        if ($this->is_midterm)
            $sum = $sum / 78.0 * 100;
        else
            $sum = $sum / 117.0 * 100;

        $this->grade = $this->_calGrade($sum);

        $this->edit_date = date('Y-m-d H:i:s');
    }

    public function _calGrade($score)
    {
        if ($score < 50)
            return 'E';
        if ($score < 55)
            return 'D';
        if ($score < 60)
            return 'D+';
        if ($score < 65)
            return 'C';
        if ($score < 70)
            return 'C+';
        if ($score < 75)
            return 'B';
        if ($score < 80)
            return 'B+';
        return 'A';
    }

    public function beforeValidationOnCreate()
    {
        $this->report_advisor = 0;
        $this->present_advisor = 0;
        $this->report_coadvisor = 0;
        $this->present_coadvisor = 0;
        $this->progress_report = 0;
        $this->grade = ' ';
        $this->edit_date = date('Y-m-d H:i:s');
    }
}

?>
