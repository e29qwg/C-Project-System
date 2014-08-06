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
	}

    public function updateAction()
    {
        $auth = $this->session->get('auth');
        $request = $this->request;
        $user_id = $auth['id'];

        $user = User::findFirst("id='$user_id'");
        $facebook = $request->getPost('facebook');
        $interesting = $request->getPost('interesting');

        if (empty($interesting))
            $interesting = 'ยังไม่ระบุ';

        $user->facebook = $facebook;
        $user->interesting = $interesting;

        $user->save();
        $this->flashSession->success("Update profile success");
        $this->response->redirect("profile/index/".$user_id);
    }

    public function updateProfileAction()
    {
    }

    public function saveAction()
    {
    }

    public function indexAction()
    {
    }
}

?>
