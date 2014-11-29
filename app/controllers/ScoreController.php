<?php

class ScoreController extends ControllerBase
{
    public function initialize()
    {
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function advisorViewAction()
    {
        //        $auth = $this->session->get('auth');
        //        $this->Score->advisorView();
        //        $this->DownloadFile->download($auth['id']);
        //        $this->view->disable();

        $this->flash->notice('กำลังปรับปรุงยังไม่สามารถใช้งานได้ในขณะนี้');
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

        if (!empty($all))
            $this->Score->createScoreForm();
        else
            $this->Score->createScoreForm($advisors);

        $this->DownloadFile->download("ScoreDraff");
        $this->view->disable();
    }

    public function manageScoreAction()
    {
    }
}

?>
