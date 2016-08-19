<?php

class ScoreController extends ControllerBase
{
    public function initialize()
    {
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function studentViewAction()
    {
        $this->view->setTemplateAfter('main');
    }

    public function uploadScoreAction()
    {
        $request = $this->request;

        if ($request->hasFiles())
        {
            foreach ($request->getUploadedFiles() as $file)
            {
                $this->Score->uploadScore($file->getTempName());
                unlink($file->getTempName());
                $this->flashSession->success('อัพโหลดสำเร็จ');
                return $this->response->redirect('score/manageScore');
            }
        }
        $this->flashSession->error('ไม่พบไฟล์');
        return $this->response->redirect('score/manageScore');
    }

    public function createScoreAction()
    {
        $request = $this->request;
        $all = $request->getPost("all");
        $advisors = $request->getPost("advisor");
        $semester_id = $request->getPost('semester_id');

        if (!empty($all))
            $this->Score->createScoreForm($semester_id);
        else
            $this->Score->createScoreForm($advisors, $semester_id);

        $this->DownloadFile->download("ScoreDraff");
        $this->view->disable();
    }

    public function manageScoreAction()
    {
        $this->_getAllSemester();
    }
}

?>
