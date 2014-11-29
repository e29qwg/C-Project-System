<?php

class AdvisorController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('advisorside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function setQuotaAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('index');
        }

        $ids = $request->getPost('id');
        $nquotas = $request->getPost('quota');

        $auth = $this->session->get('auth');
        $user_id = $auth['id'];

        $count = 0;

        foreach ($nquotas as $nquota)
        {
            $id = $ids[$count++];
            $quota = Quota::findFirst("advisor_id='$id'");

            if (!$quota)
                continue;

            if ($auth['type'] != 'Admin' && $id != $user_id)
                continue;

            $quota->quota_pp = $nquota;
            $quota->save();
        }

        $this->flashSession->success('บันทึกข้อมูลสำเร็จ');
        $this->response->redirect('advisor/quota');
    }

    public function quotaAction()
    {
    }

    public function advisorListAction()
    {
        $this->view->setTemplateAfter('main');
    }

    public function indexAction()
    {
    }
}

?>
