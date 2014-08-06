<?php

class ScoreProject extends \Phalcon\Mvc\Model
{
    public $score_id;
    public $user_id;
    public $project_id;
    public $report_advisor;
    public $present_advisor;
    public $system_advisor;
    public $report_coadvisorI;
    public $present_coadvisorI;
    public $system_coadvisorI;
    public $report_coadvisorII;
    public $present_coadvisorII;
    public $system_coadvisorII;
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
        $sum = 0;
        $sum += $this->report_advisor;
        $sum += $this->present_advisor;
        $sum += $this->system_advisor;
        $sum += $this->report_coadvisorI;
        $sum += $this->present_coadvisorI;
        $sum += $this->system_coadvisorI;
        $sum += $this->report_coadvisorII;
        $sum += $this->present_coadvisorII;
        $sum += $this->system_coadvisorII;
        $sum += $this->progress_report;

        if ($this->is_midterm)
            $sum = $sum/115.0*100;
        else
            $sum = $sum/175.0*100;

        $this->grade = $this->_calGrade($sum);

        $this->edit_date = date('Y-m-d H:i:s');
    }

    public function beforeValidationOnCreate()
    {
        $this->report_advisor = 0;
        $this->present_advisor = 0;
        $this->system_advisor = 0;
        $this->report_coadvisorI = 0;
        $this->present_coadvisorI = 0;
        $this->system_coadvisorI = 0;
        $this->report_coadvisorII = 0;
        $this->present_coadvisorII = 0;
        $this->system_coadvisorII = 0;
        $this->progress_report = 0;
        $this->grade = ' ';
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
}

?>
