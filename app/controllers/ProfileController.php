<?php

class ProfileController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function advisorProfileAction()
    {
        $this->_getAllSemester();
    }

    /**
     *
     */
    public function setNotificationAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            return $this->forward('index/updateProfile');
        }

        $transaction = $this->transactionManager->get();

        try
        {
            $projectUpdate = ($request->getPost('project_update') == 'on');
            $progressUpdate = ($request->getPost('progress_update') == 'on');

            $notifications = Notification::find(array(
               "conditions" => "user_id=:user_id:",
                "bind" => array("user_id" => $this->auth['id'])
            ));

            foreach ($notifications as $notification)
            {
                $notification->setTransaction($transaction);
                if (!$notification->delete())
                    $transaction->rollback('Error when delete old data');
            }

            if ($projectUpdate)
            {
                $notification = new Notification();
                $notification->setTransaction($transaction);
                $notification->user_id = $this->auth['id'];
                $notification->noption = 'project_update';
                if (!$notification->save())
                    $transaction->rollback('Error when update data');
            }

            if ($progressUpdate)
            {
                $notification = new Notification();
                $notification->setTransaction($transaction);
                $notification->user_id = $this->auth['id'];
                $notification->noption = 'progress_update';
                if (!$notification->save())
                    $transaction->rollback('Error when update data');
            }

            $transaction->commit();
        }
        catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction Failed: '. $e->getMessage());
            return $this->forward('profile/updateProfile');
        }


        $this->flashSession->success('Update success');
        $this->response->redirect('profile/updateProfile');

    }

    public function updateAction()
    {
        $auth = $this->auth;
        $request = $this->request;
        $user_id = $auth['id'];

        if ($auth['type'] == 'Admin')
        {
            $advisor_id = $request->getPost('advisor_id');
            if (!empty($advisor_id))
                $user_id = $advisor_id;
        }

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));
        $facebook = $request->getPost('facebook');
        $interesting = $request->getPost('interesting');
        $email = $request->getPost('email');
        $title = $request->getPost('title');

        if (empty($interesting))
            $interesting = 'ยังไม่ระบุ';

        if ($request->hasFiles())
        {
            foreach ($request->getUploadedFiles() as $file)
            {
                $file->moveTo('./profilePicture/' . $user->user_id . '.img');
            }
        }

        $user->title = $title;
        $user->facebook = $facebook;
        $user->interesting = $interesting;
        $user->email = $email;

        $user->save();
        $this->flashSession->success("Update profile success");
        $this->response->redirect("profile/index/" . $user_id);
    }

    public function updateProfileAction()
    {
        $notifications = Notification::find(array(
            "conditions" => "user_id=:user_id:",
            "bind" => array("user_id" => $this->auth['id'])
        ));

        foreach ($notifications as $notification)
        {
            if ($notification->noption == 'project_update')
            {
                \Phalcon\Tag::setDefault('project_update', 'on');
            }

            if ($notification->noption == 'progress_update')
            {
                \Phalcon\Tag::setDefault('progress_update', 'on');
            }
        }
    }

    public function saveAction()
    {
    }

    public function indexAction()
    {
    }
}

?>
