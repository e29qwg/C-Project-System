<?php

class Exam extends Phalcon\Mvc\User\Component
{
    //generate exam
    public function generateExam()
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];
        $excel = PHPExcel_IOFactory::createReader('Excel2007');
        $obj = $excel->load('./excel/exam.xlsx');

        $records = $this->modelsManager->createBuilder()->from(array("Project", "ProjectMap"))->where("
                Project.project_id = ProjectMap.project_id AND 
                ProjectMap.map_type='advisor' AND 
                Project.project_status='Accept'")->orderBy("ProjectMap.user_id ASC")->getQuery()->execute();

        $row = array(0, 5, 5, 5);

        foreach ($records as $record)
        {
            $project = $record->project;
            $obj->setActiveSheetIndex($project->project_level_id - 1);
            $projectMapOwners = ProjectMap::find("project_id='$project->project_id' AND map_type = 'owner'");
            $advisorMaps = ProjectMap::find(array("project_id='$project->project_id' AND map_type != 'owner'", "order" => "map_type,user_id ASC"));

            foreach ($projectMapOwners as $projectMapOwner)
            {
                $useRow = $row[$project->project_level_id];
                $owner = User::findFirst("id=$projectMapOwner->user_id");
                $obj->getActiveSheet()->setCellValue('B' . $useRow, $owner->user_id);
                $obj->getActiveSheet()->setCellValue('C' . $useRow, $owner->title . $owner->name);
                $obj->getActiveSheet()->setCellValue('D' . $useRow, $project->project_name);

                $column = 'E';

                foreach ($advisorMaps as $advisorMap)
                {
                    $advisor = User::findFirst("id='$advisorMap->user_id'");
                    $obj->getActiveSheet()->setCellValue($column . $useRow, $advisor->title . $advisor->name);
                    $column++;
                }
                $row[$project->project_level_id]++;
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $hash = $this->security->getToken();
        $objWriter->save('excel/' . $hash . '.xlsx');
        $buffer = file_get_contents('excel/' . $hash . '.xlsx');
        $excel = ExcelFile::findFirst("common_name='ExamDraff'");
        if (!$excel)
            $excel = new ExcelFile();
        $excel->user_id = $user_id;
        $excel->filename = $hash . '.xlsx';
        $excel->file = $buffer;
        $excel->common_name = 'ExamDraff';
        $excel->public = 0;
        $excel->save();
        unlink('excel/' . $hash . '.xlsx');
    }
}

?>
