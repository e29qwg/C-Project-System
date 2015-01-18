<?php

class AdvisorController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('advisorside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function removeAdvisorAction()
    {
        $id = $this->dispatcher->getParam(0);

        $transaction = $this->transactionManager->get();

        try
        {
            $user = User::findFirst(array(
                "conditions" => "id=:id:",
                "bind" => array("id" => $id)
            ));

            $user->setTransaction($transaction);

            $user->type='Staff';
            if (!$user->save())
                $transaction->rollback('Error when change type');

            $quota = Quota::findFirst(array(
                "conditions" => "advisor_id=:id:",
                "bind" => array("id" => $id)
            ));

            if (!$quota)
                $transaction->rollback('Error when delete quota');

            if (!$quota->delete())
                $transaction->rollback('Error when delete quota');

            $transaction->commit();
        }
        catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: '.$e);
            return $this->forward('advisor/manageAdvisor');
        }

        $this->flashSession->success('Remove advisor success');
        return $this->forward('advisor/manageAdvisor');
    }

    public function addAdvisorAction()
    {
        $id = $this->dispatcher->getParam(0);

        $transaction = $this->transactionManager->get();

        try
        {
            $user = User::findFirst(array(
                "conditions" => "id=:id:",
                "bind" => array("id" => $id)
            ));

            $user->setTransaction($transaction);

            $user->type='Advisor';
            if (!$user->save())
                $transaction->rollback('Error when change type');

            //create quota default by 30
            $quota = new Quota();
            $quota->setTransaction($transaction);
            $quota->quota_pp = 30;
            $quota->advisor_id = $id;

            if (!$quota->save())
                $transaction->rollback('Error when create quota');

            $transaction->commit();
        }
        catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction failure: '.$e);
            return $this->forward('advisor/manageAdvisor');
        }

        $this->flashSession->success('Add advisor success');
        return $this->forward('advisor/manageAdvisor');
    }

    public function manageAdvisorAction()
    {
        $advisors = User::find(array(
            "conditions" => "type=:type:",
            "bind" => array("type" => "Advisor")
        ));

        $this->view->setVar('advisors', $advisors);

        $staffs = User::find(array(
            "conditions" => "type=:type:",
            "bind" => array("type" => "Staff")
        ));

        $this->view->setVar('staffs', $staffs);
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
