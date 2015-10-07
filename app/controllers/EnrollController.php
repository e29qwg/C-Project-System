<?php

class EnrollController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('adminside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function setUserAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('enroll');
        }

        $semester_id = $request->getPost('semester_id');

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
                    $filename = $file->getTempName();
                    $file->moveTo('excel/'.$file->getName());
                    $filename = 'excel/'.$file->getName();
                    $inputFileType = PHPExcel_IOFactory::identify($filename);
                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
                    $objReader->setReadDataOnly(true);

                    $objPHPExcel = $objReader->load($filename);
                    $sheet = $objPHPExcel->setActiveSheetIndex(0);

                    $row = 3;

                    $oldRecords = Enroll::find(array(
                        "conditions" => "semester_id=:semester_id: AND project_level_id=:project_level_id:",
                        "bind" => array("semester_id" => $semester_id, "project_level_id" => $project_level_id)
                    ));

                    foreach ($oldRecords as $oldRecord)
                    {
                        $oldRecord->setTransaction($transaction);
                        if (!$oldRecord->delete())
                        {
                            $transaction->rollback("Error when clear old data");
                        }
                    }

                    while (true)
                    {
                        $student_id = $sheet->getCell('A' . $row)->getCalculatedValue();
                        if (empty($student_id))
                            break;

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
            }
        }

        $this->flashSession->success('บันทึกสำเร็จ');
        $this->response->redirect('enroll');
    }

    public function indexAction()
    {
        $this->_getAllSemester();
    }
}

?>
