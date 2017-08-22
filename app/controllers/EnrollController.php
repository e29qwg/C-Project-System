<?php

class EnrollController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('adminside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function clearUserAction()
    {
        $oldRecords = Enroll::find();
        $transaction = $this->transactionManager->get();

        try
        {

            foreach ($oldRecords as $oldRecord)
            {
                $oldRecord->setTransaction($transaction);
                if (!$oldRecord->delete())
                {
                    $transaction->rollback("Error when clear old data");
                }
            }

            $transaction->commit();

        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: ' . $e->getMessage());
            return $this->forward('enroll');
        }

        $this->flashSession->success('ลบช้อมูลสำเร็จ');
        return $this->response->redirect('enroll');
    }

    public function setUserAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('enroll');
        }

        $semester_id = $this->current_semester;

        if ($request->hasFiles())
        {

            //begin transaction
            $transaction = $this->transactionManager->get();

            try
            {
                foreach ($request->getUploadedFiles() as $file)
                {
                    $key = $file->getKey();
                    if ($key == 'pp_file')
                        $project_level_id = 1;
                    else if ($key == 'p1_file')
                        $project_level_id = 2;
                    else if ($key == 'p2_file')
                        $project_level_id = 3;

                    //read excel file
                    $file->moveTo('excel/' . $file->getName());
                    $filename = 'excel/' . $file->getName();
                    $inputFileType = PHPExcel_IOFactory::identify($filename);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objReader->setReadDataOnly(true);

                    $objPHPExcel = $objReader->load($filename);
                    $sheet = $objPHPExcel->setActiveSheetIndex(0);

                    $row = 1;

                    while (true)
                    {
                        $student_id = $sheet->getCell('A' . $row)->getValue();
                        if (empty($student_id))
                            break;

                        $enroll = Enroll::findFirst([
                           "conditions" => "student_id=:student_id: AND semester_id=:semester_id: AND project_level_id=:project_level_id:",
                           "bind" => ["student_id" => $student_id, "semester_id" => $semester_id, "project_level_id" => $project_level_id]
                        ]);

                        if ($enroll)
                            continue;

                        $enroll = new Enroll();
                        $enroll->setTransaction($transaction);
                        $enroll->student_id = $student_id;
                        $enroll->semester_id = $semester_id;
                        $enroll->project_level_id = $project_level_id;

                        if (!$enroll->save())
                        {
                            $transaction->rollback('Error when insert new data');
                        }

                        $row++;
                    }

                    unlink($filename);
                }

                $transaction->commit();
            } catch (Phalcon\Mvc\Model\Transaction\Failed $e)
            {
                $this->flash->error('Transaction failure: ' . $e->getMessage());
                return $this->forward('enroll');
            }
        }

        $this->flashSession->success('บันทึกสำเร็จ');
        return $this->response->redirect('enroll');
    }

    public function deleteUserAction()
    {
        $id = $this->dispatcher->getParam(0);

        $enroll = Enroll::findFirst([
            "conditions" => "id=:id:",
            "bind" => ["id" => $id]
        ]);

        if ($enroll)
            $enroll->delete();

        $this->flashSession->success('delete success');
        return $this->_redirectBack();
    }

    public function indexAction()
    {
        $enrolls = Enroll::find([
            "conditions" => "semester_id=:semester_id:",
            "bind" => ["semester_id" => $this->current_semester]
        ]);

        $this->view->setVar('enrolls', $enrolls);
    }

    public function addUserAction()
    {
        $projectLevels = ProjectLevel::find();
        $this->view->setVar('projectLevels', $projectLevels);
    }

    public function doAddUserAction()
    {
        $request = $this->request;

        $project_level = $request->getPost('project_level');
        $student_id = $request->getPost('student_id');

        $enroll = new Enroll();
        $enroll->student_id = $student_id;
        $enroll->project_level_id = $project_level;
        $enroll->semester_id = $this->current_semester;

        if (!$enroll->save())
        {
            $this->dbError($enroll);
            return $this->forward('enroll/addUser');
        }

        $this->flashSession->success('Add user success');
        return $this->response->redirect('enroll#manage');
    }
}

?>
