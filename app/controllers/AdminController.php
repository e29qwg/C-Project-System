<?php

class AdminController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('adminside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function sendNewsAction()
    {
        $users = User::find();

        foreach ($users as $user)
        {
            if (!empty($user->email))
                $this->sendMail('System update!!!!', 'หัวข้อโครงงานถูกปรับให้มีความยาวสูงสุดเพียง 50 ตัวอักษร ทำให้บางโครงงานรายชื่อจะขาดหายไป กรุณาตรวจสอบและแก้ไขให้ตรงเงื่อนไข ขอบคุณครับ', $user->email);
        }
    }

    public function excelCoadvisorAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid request');
            return $this->forward('admin/manageCoadvisor');
        }

        $semester_id = $request->getPost('semester_id');

        //fetch db
        $records = $this->modelsManager->createBuilder();
        $records->from(array('Project', 'ProjectMap'));
        $records->where("Project.project_status='Accept' AND Project.semester_id=:semester_id:", array("semester_id" => $semester_id));
        $records->andWhere("ProjectMap.map_type='advisor'");
        $records->andWhere("Project.project_id=ProjectMap.project_id");
        $records->orderBy("Project.project_level_id, ProjectMap.user_id ASC");
        $records->distinct(true);
        $records = $records->getQuery()->execute();


        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("CoE Project");
        $objPHPExcel->setActiveSheetIndex(0);

        $sheet = $objPHPExcel->getActiveSheet();

        //Deprecated code.
       /* PHPExcel_Shared_Font::setTrueTypeFontPath('/usr/share/fonts/truetype/');
        PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);*/

        for ($col = 'A'; $col <= 'G'; $col++)
            $sheet->getColumnDimension($col)->setAutoSize(true);

        //set table header
        $sheet->SetCellValue('A1', 'รหัสนักศึกษา');
        $sheet->SetCellValue('B1', 'ชื่อ');
        $sheet->setCellValue('C1', 'ชื่อโครงงาน');
        $sheet->setCellValue('D1', 'ระดับโครงงาน');
        $sheet->setCellValue('E1', 'อาจารย์ที่ปรึกษา');
        $sheet->setCellValue('F1', 'อาจารย์ที่ปรึกษาร่วม');
        $sheet->setCellValue('G1', 'อาจารย์ที่ปรึกษาร่วม');
        $sheet->setCellValue('H1', 'รหัสโครงงาน');

        $row = 2;

        $advisors = User::find("type='Advisor'");
        $arr = array();

        foreach ($advisors as $ad)
            array_push($arr, $ad->name);

        $advisors = implode(",", $arr);
        $advisors = '"' . $advisors . '"';

        foreach ($records as $record)
        {

            $project = $record->project;

            //fetch owner
            $owners = ProjectMap::find(array(
                "conditions" => "project_id=:project_id: AND map_type='owner'",
                "bind" => array("project_id" => $project->project_id)
            ));

            //fetch advisor
            $projectMap = ProjectMap::findFirst(array(
                "conditions" => "project_id=:project_id: AND map_type='advisor'",
                "bind" => array("project_id" => $project->project_id)
            ));

            $advisor = User::findFirst(array(
                "conditions" => "id=:user_id:",
                "bind" => array("user_id" => $projectMap->user_id)
            ));

            $projectMaps = ProjectMap::find(array(
                "conditions" => "project_id=:project_id: AND map_type='coadvisor'",
                "bind" => array("project_id" => $project->project_id)
            ));

            foreach ($owners as $owner)
            {
                //get student id of owner
                $user = User::findFirst(array(
                    "conditions" => "id=:user_id:",
                    "bind" => array("user_id" => $owner->user_id)
                ));

                $sheet->setCellValue('A' . $row, $user->user_id);
                $sheet->setCellValue('B' . $row, $user->title . $user->name);
                $sheet->setCellValue('C' . $row, $project->project_name);

                switch ($project->project_level_id)
                {
                    case 1:
                        $projectLevel = 'PP';
                        break;
                    case 2:
                        $projectLevel = '1';
                        break;
                    case 3:
                        $projectLevel = '2';
                        break;
                }

                $sheet->setCellValue('D' . $row, $projectLevel);
                $sheet->setCellValue('E' . $row, $advisor->name);

                for ($count = 0; $count < 2; $count++)
                {
                    if (!$count)
                        $objValidation = $objPHPExcel->getActiveSheet()->getCell('F' . $row)->getDataValidation();
                    else
                        $objValidation = $objPHPExcel->getActiveSheet()->getCell('G' . $row)->getDataValidation();

                    $objValidation->setType(PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $objValidation->setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION);
                    $objValidation->setAllowBlank(true);
                    $objValidation->setShowInputMessage(true);
                    $objValidation->setShowErrorMessage(true);
                    $objValidation->setShowDropDown(true);
                    $objValidation->setErrorTitle('Input error');
                    $objValidation->setError('Value is not in list.');
                    $objValidation->setPromptTitle('Pick from list');
                    $objValidation->setPrompt('Please pick a value from the drop-down list.');
                    //
                    $objValidation->setFormula1($advisors);

                    //set coadvisor if exists
                    if (!empty($projectMaps[$count]))
                    {
                        $coadvisor = User::findFirst(array(
                            "conditions" => "id=:user_id:",
                            "bind" => array("user_id" => $projectMaps[$count]->user_id)
                        ));

                        if (!$count)
                            $sheet->setCellValue('F' . $row, $coadvisor->name);
                        else
                            $sheet->setCellValue('G' . $row, $coadvisor->name);
                    }

                    $sheet->setCellValue('H' . $row, $project->project_id);

                }
            }

            $row++;
        }

        header("Content-Disposition: attachment;filename=coadvisor.xlsx");
        header("Content-Transfer-Encoding: binary ");

        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save('php://output');
        $this->view->disable();
    }

    public function filterCoadvisorAction()
    {
        $this->_getAllSemester();
        $this->view->setTemplateAfter('adminside');

        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid request');
            return $this->forward('admin/manageCoadvisor');
        }

        $semester_id = $request->getPost('semester_id');

        $records = $this->modelsManager->createBuilder();
        $records->from(array('Project', 'ProjectMap'));
        $records->where("Project.project_status='Accept' AND Project.semester_id=:semester_id:", array("semester_id" => $semester_id));
        $records->andWhere("ProjectMap.map_type='advisor'");
        $records->distinct("Project.project_id");
        $records->andWhere("Project.project_id=ProjectMap.project_id");
        $records->orderBy("Project.project_level_id, ProjectMap.user_id ASC");
        $records = $records->getQuery()->execute();

        $this->view->setVar('records', $records);
        $this->view->setVar('semester_id', $semester_id);
    }

    public function manageCoadvisorAction()
    {
        $this->_getAllSemester();
        $this->view->setTemplateAfter('adminside');

        $request = $this->request;

        $currentSemesterId = $this->view->getVar('currentSemesterId');

        $records = $this->modelsManager->createBuilder();
        $records->from(array('Project', 'ProjectMap'));
        $records->where("Project.project_status='Accept' AND Project.semester_id=:semester_id:", array("semester_id" => $currentSemesterId));
        $records->andWhere("ProjectMap.map_type='advisor'");
        $records->distinct("Project.project_id");
        $records->andWhere("Project.project_id=ProjectMap.project_id");
        $records->orderBy("Project.project_level_id, ProjectMap.user_id ASC");
        $records = $records->getQuery()->execute();

        $this->view->setVar('records', $records);

        //post request
        if ($request->isPost())
        {
            if ($request->hasFiles(true))
            {
                foreach ($request->getUploadedFiles() as $file)
                {
                    break;
                }

                $filename = $file->getTempName();
                $inputFileType = PHPExcel_IOFactory::identify($filename);
                $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                $objReader->setReadDataOnly(true);

                $objPHPExcel = $objReader->load($filename);
                $sheet = $objPHPExcel->setActiveSheetIndex(0);

                try
                {
                    $transactionManager = $this->transactionManager;
                    $transaction = $transactionManager->get();

                    $row = 2;
                    while (true)
                    {
                        $project_id = $sheet->getCell('H' . $row)->getValue();

                        if (empty($project_id))
                            break;

                        $projectMaps = ProjectMap::find(array(
                            "conditions" => "project_id=:project_id: AND map_type='coadvisor'",
                            "bind" => array("project_id" => $project_id)
                        ));

                        foreach ($projectMaps as $projectMap)
                        {
                            $projectMap->setTransaction($transaction);
                            $projectMap->delete();
                        }

                        $coadvisor1Name = $sheet->getCell('F' . $row)->getValue();
                        $coadvisor2Name = $sheet->getCell('G' . $row)->getValue();

                        if (!empty($coadvisor1Name))
                        {
                            $user = User::findFirst(array(
                                "conditions" => "name=:name: AND type='Advisor'",
                                "bind" => array("name" => $coadvisor1Name)
                            ));

                            if (!$user)
                            {
                                $transaction->rollback('Coadvisor not found in cell ' . 'F' . $row);
                            }

                            $projectMap = new ProjectMap();
                            $projectMap->setTransaction($transaction);
                            $projectMap->user_id = $user->id;
                            $projectMap->project_id = $project_id;
                            $projectMap->map_type = 'coadvisor';

                            if (!$projectMap->save())
                            {
                                $transaction->rollback('Error when create project map');
                            }
                        }

                        if (!empty($coadvisor2Name))
                        {
                            $user = User::findFirst(array(
                                "conditions" => "name=:name: AND type='Advisor'",
                                "bind" => array("name" => $coadvisor2Name)
                            ));

                            if (!$user)
                            {
                                $transaction->rollback('Coadvisor not found in cell ' . 'G' . $row);
                            }

                            $projectMap = new ProjectMap();
                            $projectMap->setTransaction($transaction);
                            $projectMap->user_id = $user->id;
                            $projectMap->project_id = $project_id;
                            $projectMap->map_type = 'coadvisor';

                            if (!$projectMap->save())
                            {
                                $transaction->rollback('Error when create project map');
                            }
                        }

                        $row++;
                    }

                    $transaction->commit();
                }
                catch (Phalcon\Mvc\Model\Transaction\Failed $e)
                {
                    $this->flashSession->error('Transaction failure: ' . $e->getMessage());
                }

                unlink($file->getTempName());
            }
            else
            {

                try
                {
                    $transactionManager = $this->transactionManager;
                    $transaction = $transactionManager->get();

                    //http raw request
                    $project_ids = $request->getPost('project_id');
                    $coadvisors = $request->getPost('coadvisor');

                    $count = 0;

                    //TODO optimize
                    foreach ($project_ids as $project_id)
                    {
                        $projectMaps = ProjectMap::find(array(
                            "conditions" => "project_id=:project_id: AND map_type='coadvisor'",
                            "bind" => array("project_id" => $project_id)
                        ));

                        foreach ($projectMaps as $projectMap)
                        {
                            $projectMap->setTransaction($transaction);
                            if (!$projectMap->delete())
                            {
                                $transaction->rollback("Error when delete old map");
                            }
                        }

                        for ($i = 0; $i < 2; $i++, $count++)
                        {
                            if (empty($coadvisors[$count]))
                                continue;
                            $projectMap = new ProjectMap();
                            $projectMap->setTransaction($transaction);
                            $projectMap->user_id = $coadvisors[$count];
                            $projectMap->project_id = $project_id;
                            $projectMap->map_type = 'coadvisor';
                            if (!$projectMap->save())
                            {
                                foreach ($projectMap->getMessages() as $mes)
                                    $transaction->rollback($mes);
                            }
                        }
                    }

                    $transaction->commit();
                }
                catch (Phalcon\Mvc\Model\Transaction\Failed $e)
                {
                    $this->flashSession->error('Transaction failure: ' . $e->getMessage());
                }
            }

            $this->flashSession->success('บันทึกสำเร็จ');
            $this->response->redirect('admin/manageCoadvisor');
        }
    }

    public function summaryTopicExportAction()
    {
        //inherit call updatetopic
        $this->DownloadFile->download('Topic');
        $this->view->disable();
    }

    public function advisorProfileAction()
    {
        $this->view->setTemplateAfter('adminside');
    }

    public function summaryTopicAction()
    {
        $currentSemester = Settings::findFirst("name='current_semester'");

        if (!$currentSemester)
        {
            $this->flash->error('setting error');
            return $this->forward('admin');
        }

        $semester = $this->request->getPost('semester');

        if (empty($semester))
        {
            $projects = Project::find(array(
                "conditions" => "semester_id=:semester_id:",
                "bind" => array("semester_id" => $currentSemester->value)
            ));
            $this->view->setVar('semester', $currentSemester->value);
        }
        else
        {
            $projects = Project::find(array(
                "conditions" => "semester_id=:semester_id:",
                "bind" => array("semester_id" => $semester)

            ));
            $this->view->setVar('semester', $semester);
        }

        $this->Topic->updateTopic($projects);
        $this->view->setVar('projects', $projects);

        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);

        $this->view->setTemplateAfter('adminside');
    }


    public function indexAction()
    {
        $this->view->setTemplateAfter('adminside');
    }

    public function setViewAction()
    {
        $this->view->setTemplateAfter('adminside');
    }

    public function changeViewAction()
    {
        $this->view->setTemplateAfter('adminside');
        $auth = $this->session->get('auth');
        $view = $this->request->getPost('view');
        $auth['view'] = $view;
        $this->session->set('auth', $auth);

        if ($view == 'Student')
            return $this->forward('student');

        if ($view == 'Advisor')
            return $this->forward('advisor');

        if ($view == 'Admin')
            return $this->forward('admin');
    }
}

?>
