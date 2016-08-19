<?php

class ExamController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function showExamAction()
    {
        $this->downloadAction();
    }

    public function downloadAction()
    {
        if ($this->DownloadFile->download("Exam".$this->current_semester))
        {
            $this->view->disable();
            return;
        }
        else
            $this->flash->error('ไม่พบข้อมูล');
    }

    public function doUploadAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('exam/manage');
        }

        if ($this->request->hasFiles())
        {
            foreach ($this->request->getUploadedFiles() as $file)
            {
                $examFile = file_get_contents($file->getTempName());
                $name = $file->getName();
                unlink($file->getTempName());
                $excelFile = ExcelFile::findFirst(array(
                    "conditions" => "common_name=:name:",
                    "bind" => array("name" => 'Exam' . $request->getPost('semester_id'))
                ));

                if (!$excelFile)
                {
                    $excelFile = new ExcelFile();
                    $excelFile->common_name = 'Exam' . $request->getPost('semester_id');
                }

                $excelFile->filename = $name;
                $excelFile->file = $examFile;
                $excelFile->user_id = 0;
                $excelFile->public = 1;
                $excelFile->save();

                $this->flashSession->success('อัพโหลดตารางสอบสำเร็จ');
                return $this->response->redirect('exam/manage');
            }
        }

        $this->flashSession->error('ไม่พบไฟล์ที่เลือก');
        return $this->response->redirect('exam/manage');
    }

    public function uploadAction()
    {
        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);
    }

    public function editAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('exam/manage');
        }

        if (!$this->DownloadFile->download("Exam" . $request->getPost('semester_id')))
        {
            $this->flash->error('ไม่พบตารางสอบ');
        }
    }

    public function manageAction()
    {
        $semesters = Semester::find();
        $allSemesters = array();

        foreach ($semesters as $semester)
        {
            $allSemesters[$semester->semester_id] = $semester->semester_term . '/' . $semester->semester_year;
        }

        $this->view->setVar('allSemesters', $allSemesters);
    }

    public function generateAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('admin');
        }

        $this->Exam->generateExamTable($request->getPost('semester_id'));
        $this->DownloadFile->download("ExamDraff");
        $this->view->disable();
    }
}

?>
