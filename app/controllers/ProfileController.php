<?php

class ProfileController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('main');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();

        $this->loadOwnerProject();

        if ($this->auth['type'] != 'Student')
            $this->loadAdvisorProject();
    }

    public function confirmActivateCodeAction()
    {
        $request = $this->request;

        if (!$request->isPost())
        {
            $this->flash->error('Invalid Request');
            $this->forward('profile/verifyEmail');
            return;
        }

        $activate_code = $request->getPost('activate_code');

        $user = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $this->auth['id'])
        ));

        if (!$user)
        {
            $this->flashSession->error('Internal error');
            $this->session->remove('auth');
            return $this->response->redirect('session');
        }

        if ($user->activate_code != $activate_code)
        {
            $this->flash->error('Activate code invalid');
            $this->forward('profile/verifyEmail');
            return;
        }

        $user->activate_code = null;
        $user->active = 1;

        if (!$user->save())
        {
            $this->dbError($user);
            $this->flashSession->error('Database error');
            return $this->_redirectBack();
        }

        //set session
        $this->flashSession->success('Activate success');
        $this->response->redirect('index');

        return true;
    }

    public function sendActivateCodeAction()
    {
        $user = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $this->auth['id'])
        ));

        if (!$user)
        {
            $this->flashSession->error('Internal error');
            $this->session->remove('auth');
            return $this->response->redirect('session');
        }

        $code = $this->security->getTokenKey();;
        $subject = 'Activate code';
        $body = 'Activate code: ' . $code;

        $user->activate_code = $code;
        $user->save();

        $this->sendMail($subject, $body, $user->email);
        return true;
    }

    public function verifyEmailAction()
    {
        $user = User::findFirst(array(
            "conditions" => "id=:id:",
            "bind" => array("id" => $this->auth['id'])
        ));

        if (!$user)
        {
            $this->internalError('session/logout');
            return;
        }

        $this->view->setVar('user', $user);
    }

    public function advisorProfileAction()
    {
        $this->_getAllSemester();
    }

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
            $this->flash->error('Transaction Failed: ' . $e->getMessage());
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

        $facebook = $request->getPost('facebook');
        $interesting = $request->getPost('interesting');
        $email = ltrim($request->getPost('email'));
        $email = rtrim($email);
        $title = $request->getPost('title');
        $tel = $request->getPost('tel');

        $user = User::findFirst(array(
            "conditions" => "id=:user_id:",
            "bind" => array("user_id" => $user_id)
        ));

        if (!$user)
            $this->forward('session/end');

        if (empty($interesting))
            $interesting = 'ยังไม่ระบุ';

        if ($request->hasFiles())
        {
            foreach ($request->getUploadedFiles() as $file)
            {
                $file->moveTo('./profilePicture/' . $user->user_id . '.img');
            }
        }

        $transaction = $this->transactionManager->get();

        try
        {
            $user->setTransaction($transaction);

            $user->title = $title;
            $user->facebook = $facebook;
            $user->interesting = $interesting;
            if ($user->email != $email)
            {
                $user->active = 0;
                $user->email = $email;
            }
            $user->tel = $tel;

            if (!$user->save())
            {
                $transaction->rollback($this->strDbError($user));
            }

            $transaction->commit();
        }
        catch (\Phalcon\Mvc\Model\Transaction\Failed $e)
        {
            $this->flash->error('Transaction Failed: ' . $e->getMessage());
            $this->forward('profile/updateProfile');
            return;
        }

        $this->flashSession->success("Update profile success");

        if (!$user->active)
        {
            $this->response->redirect('profile/verifyEmail');
            return;
        }

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
