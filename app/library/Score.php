<?php

class Score extends Phalcon\Mvc\User\Component
{
    public function uploadScore($scoreFile)
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $excel = PHPExcel_IOFactory::createReader('Excel2007');
        $obj = $excel->load($scoreFile);

        $row = array(6, 6, 6, 6);

        for ($i = 0; $i < 4; $i++)
        {
            $obj->setActiveSheetIndex($i);

            while (true)
            {
                $useRow = $row[$i];
                $user_id = $this->getValue($obj, 'A' . $useRow);

                //not exists row
                if (empty($user_id))
                    break;

                //get student
                $student = User::findFirst("user_id='$user_id'");

                if ($i < 2)
                {
                    $is_midterm = !$i;
                    $score = ScorePrepare::findFirst("user_id='$student->id' AND is_midterm='$is_midterm'");

                    if (!$score)
                        continue;

                    $score->report_advisor = $this->getValue($obj, 'G' . $useRow);
                    $score->present_advisor = $this->getValue($obj, 'H' . $useRow);
                    $score->report_coadvisor = $this->getValue($obj, 'I' . $useRow);
                    $score->present_coadvisor = $this->getValue($obj, 'J' . $useRow);
                    $score->progress_report = $this->getValue($obj, 'K' . $useRow);
                    $score->save();
                }
                else
                {
                    $is_midterm = !($i - 2);
                    $score = ScoreProject::findFirst("user_id='$student->id' AND is_midterm='$is_midterm'");

                    if (!$score)
                        continue;

                    $score->report_advisor = $this->getValue($obj, 'H' . $useRow);
                    $score->present_advisor = $this->getValue($obj, 'I' . $useRow);
                    $score->system_advisor = $this->getValue($obj, 'J' . $useRow);
                    $score->report_coadvisorI = $this->getValue($obj, 'K' . $useRow);
                    $score->present_coadvisorI = $this->getValue($obj, 'L' . $useRow);
                    $score->system_coadvisorI = $this->getValue($obj, 'M' . $useRow);
                    $score->report_coadvisorII = $this->getValue($obj, 'N' . $useRow);
                    $score->present_coadvisorII = $this->getValue($obj, 'O' . $useRow);
                    $score->system_coadvisorII = $this->getValue($obj, 'P' . $useRow);
                    $score->progress_report = $this->getValue($obj, 'Q' . $useRow);

                    $score->save();
                }

                $row[$i]++;
            }
        }
    }

    private function getValue($obj, $pos)
    {
        return $obj->getActiveSheet()->getCell($pos)->getValue();
    }

    public function advisorView()
    {
        $auth = $this->session->get('auth');
        $this->createScoreForm(array($auth['id']), $auth['id']);
    }

    public function createScoreForm()
    {
        $arg = func_get_args();
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        if (empty($arg[0]))
        {
            $users = User::find("type='advisor'");
            $advisor_ids = array();
            foreach ($users as $user)
            {
                array_push($advisor_ids, $user->id);
            }
        }
        else
        {
            $advisor_ids = $arg[0];
        }

        $currentSemester = Semester::maximum(array("column" => "semester_id"));

        //prepare
        $ppprojects = $this->modelsManager->createBuilder()->from(array(
                "Project",
                "ProjectMap"
            ))->where("Project.project_id = ProjectMap.project_id")->andWhere("Project.project_level_id=1")->andWhere("Project.semester_id='$currentSemester'")->andWhere("Project.project_status='Accept'")->andWhere("ProjectMap.map_type='advisor'")->inWhere("ProjectMap.user_id", $advisor_ids)->orderBy("ProjectMap.user_id ASC")->getQuery()->execute();

        $pprojects = $this->modelsManager->createBuilder()->from(array(
                "Project",
                "ProjectMap"
            ))->where("Project.project_id = ProjectMap.project_id")->andWhere("Project.project_level_id != 1")->andWhere("Project.semester_id='$currentSemester'")->andWhere("Project.project_status='Accept'")->andWhere("ProjectMap.map_type='advisor'")->inWhere("ProjectMap.user_id", $advisor_ids)->orderBy("ProjectMap.user_id ASC")->getQuery()->execute();

        $excel = PHPExcel_IOFactory::createReader('Excel2007');
        $obj = $excel->load('./excel/score.xlsx');

        $row = array(6, 6, 6, 6);

        foreach ($pprojects as $record)
        {
            $project_id = $record->project->project_id;
            $advisor_id = $record->projectMap->user_id;
            $advisor = User::findFirst("id='$advisor_id'");

            //get coadvisor
            $coadvisorMaps = ProjectMap::find(array(
                    "conditions" => "project_id='$project_id' AND map_type='coadvisor'",
                    "orders" => "map_type, user_id ASC"
                ));

            $scores = ScoreProject::find(array("conditions" => "project_id='$project_id'", "orders" => "user_id ASC"));

            //sheet 0 is midterm, 1 final
            foreach ($scores as $score)
            {
                $student = User::findFirst("id='$score->user_id'");
                $column = 'A';
                $useRow = $row[!$score->is_midterm + 2];
                $obj->setActiveSheetIndex(!$score->is_midterm + 2);
                $this->setValue($obj, $column++ . $useRow, $student->user_id);
                $this->setValue($obj, $column++ . $useRow, $student->title . $student->name);
                $this->setValue($obj, $column++ . $useRow, $record->project->project_name);
                $this->setValue($obj, $column++ . $useRow, $record->project->project_level_id - 1);
                $this->setValue($obj, $column++ . $useRow, $advisor->title . $advisor->name);
                //add coadvisor
                foreach ($coadvisorMaps as $coadvisorMap)
                {
                    $coadvisor = User::findFirst("id='$coadvisorMap->user_id'");
                    $this->setValue($obj, $column++ . $useRow, $coadvisor->title . $coadvisor->name);
                    $arr = $coadvisor->toArray();
                }

                $ascores = $score->toArray();
                $count = 0;
                foreach ($ascores as $ascore)
                {
                    if ($count > 2 && $count < 13)
                    {
                        if (!empty($ascore))
                            $this->setValue($obj, $column . $useRow, $ascore);
                        $column++;
                    }
                    $count++;
                }

                $row[!$score->is_midterm + 2]++;
            }
        }

        //got project and advisor
        foreach ($ppprojects as $record)
        {
            $project_id = $record->project->project_id;
            $advisor_id = $record->projectMap->user_id;
            $advisor = User::findFirst("id='$advisor_id'");

            //get coadvisor
            $coadvisorMaps = ProjectMap::find(array(
                    "conditions" => "project_id='$project_id' AND map_type='coadvisor'",
                    "orders" => "map_type, user_id ASC"
                ));

            $scores = ScorePrepare::find(array("conditions" => "project_id='$project_id'", "orders" => "user_id ASC"));

            //sheet 0 is midterm, 1 final
            foreach ($scores as $score)
            {
                $student = User::findFirst("id='$score->user_id'");
                $column = 'A';
                $useRow = $row[!$score->is_midterm];
                $obj->setActiveSheetIndex(!$score->is_midterm);
                $this->setValue($obj, $column++ . $useRow, $student->user_id);
                $this->setValue($obj, $column++ . $useRow, $student->title . $student->name);
                $this->setValue($obj, $column++ . $useRow, $record->project->project_name);
                //skip one column
                $column++;
                $this->setValue($obj, $column++ . $useRow, $advisor->title . $advisor->name);
                //add coadvisor
                foreach ($coadvisorMaps as $coadvisorMap)
                {
                    $coadvisor = User::findFirst("id='$coadvisorMap->user_id'");
                    $this->setValue($obj, $column++ . $useRow, $coadvisor->title . $coadvisor->name);
                    $arr = $coadvisor->toArray();
                }

                $ascores = $score->toArray();
                $count = 0;
                foreach ($ascores as $ascore)
                {
                    if ($count > 2 && $count < 8)
                    {
                        if (!empty($ascore))
                            $this->setValue($obj, $column . $useRow, $ascore);
                        $column++;
                    }
                    $count++;
                }

                $row[!$score->is_midterm]++;
            }
        }

        $obj->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $hash = $this->security->getToken();
        $objWriter->save('excel/' . $hash . '.xlsx');

        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $buffer = file_get_contents('excel/' . $hash . '.xlsx');

        if (empty($arg[1]))
            $excel = ExcelFile::findFirst("common_name='ScoreDraff'");
        else
            $excel = ExcelFile::findFirst("common_name='$arg[1]'");

        if (!$excel)
            $excel = new ExcelFile();
        $excel->user_id = $user_id;
        $excel->filename = $hash . '.xlsx';
        $excel->file = $buffer;
        if (empty($arg[1]))
            $excel->common_name = 'ScoreDraff';
        else
            $excel->common_name = $arg[1];
        $excel->public = 1;
        $excel->save();
        unlink('excel/' . $hash . '.xlsx');
    }

    private function setValue($obj, $pos, $val)
    {
        $obj->getActiveSheet()->setCellValue($pos, $val);
    }
}

?>
