<?php

class Topic extends Phalcon\Mvc\User\Component
{
    //generate excel
    public function updateTopic()
    {
        $projects = Project::find();
    
        $excel = PHPExcel_IOFactory::createReader('Excel2007');
        $obj = $excel->load('./excel/topic.xlsx');
        $obj->setActiveSheetIndex(0);
        //$this->autoSize($obj); 

        $levelRow = 3;
        $row = 2;
        $count = 0;
  
        //explain level id
        $projectLevels = ProjectLevel::find();
        $obj->getActiveSheet()->setCellValue('I2', 'ระดับโครงงาน');
        foreach ($projectLevels as $projectLevel)
        {
            $obj->getActiveSheet()->setCellValue('I'.$levelRow, $projectLevel->project_level_id);
            $obj->getActiveSheet()->setCellValue('J'.$levelRow, $projectLevel->project_level_name);
            $levelRow++;
        }
    
        foreach ($projects as $project)
        {
            //if ($project->project_status != 'Accept')
            //    continue;

			if ($project->project_status != 'Accept')
				$con = '(รอยืนยัน)';

            $projectMaps = ProjectMap::find("project_id='$project->project_id' AND map_type='owner'");
    
            $advisor = ProjectMap::findFirst("project_id='$project->project_id' AND map_type='advisor'");
            $advisor = User::findFirst("id='$advisor->user_id'");
    
            foreach ($projectMaps as $projectMap)
            {
                $count++;
                $row++;

                $user = User::findFirst("id='$projectMap->user_id'");
    
                $obj->getActiveSheet()->setCellValue('A'.$row, $count);
                $obj->getActiveSheet()->setCellValue('B'.$row, $user->user_id);
                $obj->getActiveSheet()->setCellValue('C'.$row, $user->title.$user->name);
                $obj->getActiveSheet()->setCellValue('D'.$row, $project->project_level_id);
                $obj->getActiveSheet()->setCellValue('E'.$row, $project->project_name.$con);
                $obj->getActiveSheet()->setCellValue('F'.$row, $advisor->name);
                $obj->getActiveSheet()->setCellValue('G'.$row, $project->create_date);
            }
        }
    
        $objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $hash = $this->security->getToken();
        $objWriter->save('excel/'.$hash.'.xlsx');
                
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $buffer = file_get_contents('excel/'.$hash.'.xlsx');

        $excel = ExcelFile::findFirst("common_name='Topic'");
        if (!$excel)
            $excel = new ExcelFile();
        $excel->user_id = $user_id;
        $excel->filename = $hash.'.xlsx';
        $excel->file = $buffer;
        $excel->common_name = 'Topic';
        $excel->public = 1;
        $excel->save();
    }
}
?>
