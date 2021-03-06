<?php

class Topic extends Phalcon\Mvc\User\Component
{
    //generate excel
    public function updateTopic($projects)
    {
        if (getenv('DISABLE_EXCEL'))
            return true;
        $excel = PHPExcel_IOFactory::createReader('Excel2007');
        $obj = $excel->load(__DIR__.'/../../public/excel/topic.xlsx');
        $obj->setActiveSheetIndex(0);

        $row = 2;
        $count = 0;

        foreach ($projects as $project)
        {
            if ($project->project_status != 'Accept')
                $con = '(รอยืนยัน)';
            else
                $con = '';

            $projectMaps = ProjectMap::find("project_id='$project->project_id' AND map_type='owner'");

            $advisor = ProjectMap::findFirst("project_id='$project->project_id' AND map_type='advisor'");
            $advisor = User::findFirst("id='$advisor->user_id'");

            foreach ($projectMaps as $projectMap)
            {
                $count++;
                $row++;

                $user = User::findFirst("id='$projectMap->user_id'");

                $obj->getActiveSheet()->setCellValue('A' . $row, $count);
                $obj->getActiveSheet()->setCellValue('B' . $row, $user->user_id);
                $obj->getActiveSheet()->setCellValue('C' . $row, $user->title . $user->name);

                switch ($project->project_level_id)
                {
                    case 1:
                        $projectLevel = "pp";
                        break;
                    case 2:
                        $projectLevel = "1";
                        break;
                    case 3:
                        $projectLevel = "2";
                        break;
                    default:
                        $projectLevel = $project->project_level_id;
                }


                $obj->getActiveSheet()->setCellValue('D' . $row, $projectLevel);
                $obj->getActiveSheet()->setCellValue('E' . $row, $project->project_name . $con);
                $obj->getActiveSheet()->setCellValue('F' . $row, $advisor->name);
                $obj->getActiveSheet()->setCellValue('G' . $row, $project->create_date);
            }
        }

        $this->autoSize($obj);

        $objWriter = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $hash = $this->security->getToken();
        $objWriter->save('excel/' . $hash . '.xlsx');

        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $buffer = file_get_contents('excel/' . $hash . '.xlsx');
        unlink('excel/' . $hash . '.xlsx');

        $excel = ExcelFile::findFirst("common_name='Topic'");
        if (!$excel)
            $excel = new ExcelFile();
        $excel->user_id = $user_id;
        $excel->filename = $hash . '.xlsx';
        $excel->file = $buffer;
        $excel->common_name = 'Topic';
        $excel->public = 1;
        $excel->save();
    }

    public function autosize($obj)
    {
        foreach (range('A', 'G') as $columnId)
        {
            $obj->getActiveSheet()->getColumnDimension($columnId)->setAutoSize(true);
        }
    }
}

?>
