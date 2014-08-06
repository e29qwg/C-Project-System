<?php

class ExamController extends ControllerBase
{
    public function initialize()
    {
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function downloadAction()
    {
        $this->DownloadFile->download("Exam");
        $this->view->disable();
    }

    public function doUploadAction()
    {
        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        if ($this->request->hasFiles())
        {
            foreach ($this->request->getUploadedFiles() as $file)
            {
                $examFile = file_get_contents($file->getTempName());
                unlink($file->getTempName());
                $excelFile = ExcelFile::findFirst("common_name='Exam'");

                if ($excelFile)
                {
                    $excelFile->filename = $this->security->getToken().'.xlsx';
                    $excelFile->file = $examFile;
                    $excelFile->save();
                }
                
                $this->flashSession->success('อัพโหลดตารางสอบสำเร็จ');
                return $this->response->redirect('exam/manage');
            }
        }

        $this->flashSession->error('ไม่พบไฟล์ที่เลือก');    
        return $this->response->redirect('exam/manage');
    }

    public function uploadAction()
    {
    }

    public function editAction()
    {
        $this->DownloadFile->download("Exam");
        $this->view->disable();
    }

    public function manageAction()
    {                
    }

    public function generateAction()
    {
        $this->Exam->generateExam();
        $this->DownloadFile->download("ExamDraff");
        $this->view->disable();
    }
}

?>
