<?php

class AdminController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateAfter('adminside');
        Phalcon\Tag::setTitle('ระบบจัดการโครงงานนักศึกษา');
        parent::initialize();
    }

    public function summaryTopicAction()
    {
        $this->view->setTemplateAfter('adminside');
        $this->Topic->updateTopic();
        $this->DownloadFile->download("Topic");
        $this->view->disable();
    }

    public function indexAction()
	{
        $this->view->setTemplateAfter('adminside');
    }

	public function setViewAction()
	{
        $this->view->setTemplateAfter('adminside');
	}

	public function changeViewAction()
	{
        $this->view->setTemplateAfter('adminside');
		$auth = $this->session->get('auth');
		$view = $this->request->getPost('view');
		$auth['view'] = $view;
		$this->session->set('auth', $auth);

		if ($view == 'Student')
			return $this->forward('student');
		
		if ($view == 'Advisor')
			return $this->forward('advisor');
		
		if ($view == 'Admin')
			return $this->forward('admin');
	}
}

?>
